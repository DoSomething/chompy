<?php

namespace Chompy\Jobs;

use Chompy\Stat;
use Carbon\Carbon;
use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Chompy\Traits\ImportToRogue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateTurboVotePostInRogue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ImportToRogue;

    /**
     * The record to be created into a post from the csv.
     *
     * @var array
     */
    protected $record;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($record)
    {
    	$this->record = $record;
    }

    /**
     * Execute the job to create a Turbo Vote post in Rogue.
     *
     * @return array
     */
    public function handle(Rogue $rogue)
    {
        $shouldProcess = $this->scrubRecord($this->record);

        if ($shouldProcess) {
            info('progress_log: Processing: ' . $this->record['id']);

            $referralCode = $this->record['referral-code'];
            $referralCodeValues = $this->parseReferralCode($referralCode);

            try {
                $user = $this->getOrCreateUser($this->record, $referralCodeValues);
            } catch (\Exception $e) {
                info('There was an error with that user: ' . $this->record['id'], [
                    'Error' => $e->getMessage(),
                ]);
            }

            if ($user) {
                $post = $rogue->getPost([
                    'campaign_id' => (int) $referralCodeValues['campaign_id'],
                    'northstar_id' => $user->id,
                    'type' => 'voter-reg',
                ]);

                if (! $post['data']) {
                    info('creating post in rogue for ' . $this->record['id']);

                    $tvCreatedAtMonth = strtolower(Carbon::parse($this->record['created-at'])->format('F-Y'));
                    $sourceDetails = isset($referralCodeValues['source_details']) ? $referralCodeValues['source_details'] : null;
                    $postDetails = $this->extractDetails($this->record);

                    $postData = [
                        'campaign_id' => (int) $referralCodeValues['campaign_id'],
                        'campaign_run_id' => (int) $referralCodeValues['campaign_run_id'],
                        'northstar_id' => $user->id,
                        'type' => 'voter-reg',
                        'action' => $tvCreatedAtMonth . '-turbovote',
                        'status' => $this->translateTVStatus($this->record['voter-registration-status'], $this->record['voter-registration-method']),
                        'source' => 'turbovote',
                        'source_details' => $sourceDetails,
                        'details' => $postDetails,
                    ];

                    try {
                        $post = $rogue->createPost($postData);

                        if ($post['data']) {
                            info('post created in rogue for ' . $this->record['id']);
                        }
                    } catch (\Exception $e) {
                        info('There was an error storing the post for: ' . $this->record['id'], [
                            'Error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    $newStatus = $this->translateTVStatus($this->record['voter-registration-status'], $this->record['voter-registration-method']);
                    $statusShouldChange = $this->updateStatus($post['data'][0]['status'], $newStatus);

                    if ($statusShouldChange) {
                        try {
                            $rogue->updatePost($post['data'][0]['id'], ['status' => $statusShouldChange]);
                        } catch (\Exception $e) {
                            info('There was an error updating the post for: ' . $this->record['id'], [
                                'Error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Determines if a record should be process to be stored or if it is not valid.
     *
     * @param array $record
     * @return bool
    */
    private function scrubRecord($record)
    {
        $isNotValidEmail = strrpos($record['email'], 'thing.org') !== false || strrpos($record['email'] !== false, '@dosome') || strrpos($record['email'], 'turbovote') !== false;
        $isNotValidHostname = strrpos($record['hostname'], 'testing') !== false;
        $isNotValidLastName = strrpos($record['last-name'], 'Baloney') !== false;

        return $isNotValidEmail || $isNotValidHostname || $isNotValidLastName ? false : true;
    }

    /**
     * Parse the referral code field to grab individual values.
     *
     * @param  array $refferalCode
     */
    private function parseReferralCode($referralCode)
    {
        $values = [];

        if (! empty($referralCode)) {
            $referralCode = explode(',', $referralCode);

            foreach ($referralCode as $value) {
                $value = explode(':', $value);

                // Grab northstar id
                if (strtolower($value[0]) === 'user') {
                    $values['northstar_id'] = $value[1];
                }

                // Grab the Campaign Id.
                if (strtolower($value[0]) === 'campaignid' || strtolower($value[0]) === 'campaign') {
                    $values['campaign_id'] = $value[1];
                }

                // Grab the Campaign Run Id.
                if (strtolower($value[0]) === 'campaignrunid') {
                    $values['campaign_run_id'] = $value[1];
                }

                // Grab the source
                if (strtolower($value[0]) === 'source') {
                    $values['source'] = $value[1];
                }

                // Grab any source details
                if (strtolower($value[0]) === 'source_details') {
                    $values['source_details'] = $value[1];
                }
            }
        }

        // Make sure we have all the values we need, otherwise, use the defaults.
        if (empty($values) || !array_has($values, ['northstar_id', 'campaign_id', 'campaign_run_id'])) {
            $values = [
                'northstar_id' => null, // set the user to null so we force account creation when the code is not present.
                'campaign_id' => 8017,
                'campaign_run_id' => 8022,
                'source' => 'turbovote',
                'source_details' => null,
            ];
        }

        return $values;
    }

    /**
     * For a given record and referral code values, first check if we have a northstar ID, then grab the user using that.
     * Otherwise, see if we can find the user with the given email (if it exists), if not check if we can find them with the given phone number (if it exists).
     * If all else fails, create the user .
     *
     * @TODO - If we have a northstar id in the referral code, then we probably don't need to make a call to northstar for the full user object.
     *
     * @return array
     */
    private function getOrCreateUser($record, $values)
    {
        $user = null;

        $userFieldsToLookFor = [
            'id' => isset($values['northstar_id']) && !empty($values['northstar_id']) ? $values['northstar_id'] : null,
            'email' => isset($record['email']) && !empty($record['email']) ? $record['email'] : null,
            'mobile' => isset($record['phone']) && !empty($record['phone']) ? $record['phone'] : null,
        ];

        foreach ($userFieldsToLookFor as $field => $value)
        {
            if ($value) {
                info('getting user with the '.$field.' field', [$field => $value]);
                $user = gateway('northstar')->asClient()->getUser($field, $value);
            }

            if ($user) {
                break;
            }
        }

        if (is_null($user)) {
            $user = gateway('northstar')->asClient()->createUser([
                'email' => $record['email'],
                'mobile' => $record['phone'],
                'first_name' => $record['first-name'],
                'last_name' => $record['last-name'],
                'addr_street1' => $record['registered-address-street'],
                'addr_street2' => $record['registered-address-street-2'],
                'addr_city' => $record['registered-address-city'],
                'addr_state' => $record['registered-address-state'],
                'addr_zip' => $record['registered-address-zip'],
                'source' => env('NORTHSTAR_CLIENT_ID'),
            ]);

            info('created user', ['user' => $user->id]);
        }

        return $user;
    }

    /**
     * Parse the record for extra details and return them as a JSON object.
     *
     * @param  array $record
     * @param  array $extraData
     */
    private function extractDetails($record, $extraData = null)
    {
        $details = [];

        $importantKeys = [
            'hostname',
            'referral-code',
            'partner-comms-opt-in',
            'created-at',
            'updated-at',
            'voter-registration-status',
            'voter-registration-source',
            'voter-registration-method',
            'voting-method-preference',
            'email subscribed',
            'sms subscribed',
        ];

        foreach ($importantKeys as $key) {
            $details[$key] = $this->record[$key];
        }

        if ($extraData) {
            $details = array_merge($details, $extraData);
        }

        return json_encode($details);
    }

    /**
     * Translate a status from TurboVote into a status that can be sent to Rogue.
     *
     * @param  string $tvStatus
     * @param  string $tvMethod
     * @return string
     */
    private function translateTVStatus($tvStatus, $tvMethod)
    {
        $translatedStatus = '';

        switch($tvStatus)
        {
            case 'initiated':
                $translatedStatus = 'register-form';
                break;
            case 'registered':
                $translatedStatus = $tvMethod === 'online' ? 'register-OVR' : 'confirmed';
                break;
            case 'unknown':
            case 'pending':
                $translatedStatus = 'uncertain';
                break;
            case 'ineligible':
            case 'not-required':
                $translatedStatus = 'ineligible';
                break;
            default:
                $translatedStatus = 'pending';
        }

        return $translatedStatus;
    }

    /*
     * Determines if a status should be changed and what it should be changed to.
     *
     * @param string $currentStatus
     * @param string $newStatus
     * @return string|null
     */
    private function updateStatus($currentStatus, $newStatus)
    {
        $statusHierarchy = [
            'uncertain',
            'ineligible',
            'confirmed',
            'register-OVR',
            'register-form',
        ];

        $indexOfCurrentStatus = array_search($currentStatus, $statusHierarchy);
        $indexOfNewStatus = array_search($newStatus, $statusHierarchy);

        return $indexOfCurrentStatus < $indexOfNewStatus ? $newStatus : null;
    }
}