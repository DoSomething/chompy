<?php

namespace Chompy\Jobs;

use Carbon\Carbon;
use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Chompy\Traits\ImportToRogue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CreateRockTheVotePostInRogue implements ShouldQueue
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
            info('progress_log: Processing: ' . $this->record['Email address']);

            $referralCode = $this->record['Tracking Source'];
            $referralCodeValues = $this->parseReferralCode($referralCode);

            $user = $this->getOrCreateUser($this->record, $referralCodeValues);

            // @TODO: If we write more functions that are identical across all voter reg imports, pull out into a ImportsVoterReg trait
            $this->updateNorthstarStatus($user, $this->translateStatus($this->record['Status'], $this->record['Finish with State']));

            $post = $rogue->getPost([
                'campaign_id' => (int) $referralCodeValues['campaign_id'],
                'northstar_id' => $user->id,
                'type' => 'voter-reg',
            ]);

            if (! $post['data']) {
                $rtvCreatedAtMonth = strtolower(Carbon::parse($this->record['Started registration'])->format('F-Y'));
                $sourceDetails = isset($referralCodeValues['source_details']) ? $referralCodeValues['source_details'] : null;
                $postDetails = $this->extractDetails($this->record);

                $postData = [
                    'campaign_id' => (int) $referralCodeValues['campaign_id'],
                    'campaign_run_id' => (int) $referralCodeValues['campaign_run_id'],
                    'northstar_id' => $user->id,
                    'type' => 'voter-reg',
                    'action' => $rtvCreatedAtMonth . '-rockthevote',
                    'status' => $this->translateStatus($this->record['Status'], $this->record['Finish with State']),
                    'source' => 'rock-the-vote',
                    'source_details' => $sourceDetails,
                    'details' => $postDetails,
                ];

                try {
                    $post = $rogue->createPost($postData);

                    if ($post['data']) {
                        info('post created in rogue for ' . $this->record['Email address']);
                    }
                } catch (\Exception $e) {
                    info('There was an error storing the post', [
                        'Error' => $e->getMessage(),
                    ]);
                }
            } else {
                $newStatus = $this->translateStatus($this->record['Status'], $this->record['Finish with State']);
                $statusShouldChange = $this->updateStatus($post['data'][0]['status'], $newStatus);

                if ($statusShouldChange) {
                    try {
                        $rogue->updatePost($post['data'][0]['id'], ['status' => $statusShouldChange]);
                    } catch (\Exception $e) {
                        info('There was an error updating the post for: ' . $this->record['Email address'], [
                            'Error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }
    }

    /*
     * Determines if a record should be process to be stored or if it is not valid.
     *
     * @param array $record
     * @return bool
    */
    private function scrubRecord($record)
    {
        $isNotValidEmail = strrpos($record['Email address'], 'thing.org') !== false || strrpos($record['Email address'] !== false, '@dosome') || strrpos($record['Email address'], 'rockthevote.com') !== false || strrpos($record['Email address'], 'test') !== false || strrpos($record['Email address'], '+') !== false;
        $isNotValidLastName = strrpos($record['Last name'], 'Baloney') !== false;

        return $isNotValidEmail || $isNotValidLastName ? false : true;
    }

    /**
     * Parse the referral code field to grab individual values.
     *
     * @param  array $refferalCode
     */
    private function parseReferralCode($referralCode)
    {
        $values = [];

        // Remove some nonsense that comes in front of the referral code sometimes
        if (strrpos($referralCode, 'iframe?r=') !== false) {
            $referralCode = str_replace('iframe?r=', null, $referralCode);
        }
        if (strrpos($referralCode, 'iframe?') !== false) {
            $referralCode = str_replace('iframe?', null, $referralCode);
        }

        if (! empty($referralCode)) {
            $referralCode = explode(',', $referralCode);

            $referral = false;

            foreach ($referralCode as $value) {

                // See if we are dealing with ":" or "="
                if (strpos($value, ':')) {
                    $value = explode(':', $value);
                }
                elseif (strpos($value, '=')) {
                    $value = explode('=', $value);
                }

                // Add northstar_id, campaign id and run, source, and source details into $values
                switch (strtolower($value[0])) {
                    case 'user':
                        $values['northstar_id'] = $value[1];
                        break;

                    case 'campaignid':
                        $values['campaign_id'] = $value[1];
                        break;

                    case 'campaign':
                        $values['campaign_id'] = $value[1];
                        break;

                    case 'campaignrunid':
                        $values['campaign_run_id'] = $value[1];
                        break;

                    case 'source':
                        $values['source'] = $value[1];
                        break;

                    case 'source_details':
                        $values['source_details'] = $value[1];
                        break;

                    default:
                        break;
                }

                // Is this a referral?
                if (strtolower($value[0]) === 'referral' && strtolower($value[1]) === 'true') {
                    $referral = true;
                }
            }
        }

        // See if we have all the required information we need
        if (!array_has($values, ['northstar_id', 'campaign_id', 'campaign_run_id'])) {
            // If we have valid campaign values, use em! This also means that we do not have NS id
            if (array_has($values, ['campaign_id', 'campaign_run_id']) && is_numeric($values['campaign_id']) && is_numeric($values['campaign_run_id'])) {
                $finalValues = [
                    'northstar_id' => null, // set the user to null so we force account creation when the code is not present.
                    'campaign_id' => $values['campaign_id'],
                    'campaign_run_id' => $values['campaign_run_id'],
                    'source' => 'rock-the-vote',
                    'source_details' => null,
                ];
            }

            // If we have NS id, use it! This also means that we do not have both campaign_id and campaign_run_id, so use the defaults
            if (array_has($values, ['northstar_id'])) {
                $finalValues = [
                    'northstar_id' => $values['northstar_id'], // set the user to null so we force account creation when the code is not present.
                    'campaign_id' => 8017,
                    'campaign_run_id' => 8022,
                    'source' => 'rock-the-vote',
                    'source_details' => null,
                ];
            }
        }

        // If we were missing all the necessary values or if this is a referral, use all the defaults
        if (empty($finalValues) || $referral) {
            $finalValues = [
                'northstar_id' => null, // set the user to null so we force account creation when the code is not present.
                'campaign_id' => 8017,
                'campaign_run_id' => 8022,
                'source' => 'rock-the-vote',
                'source_details' => null,
            ];
        }

        return $finalValues;
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
            'email' => isset($record['Email address']) && !empty($record['Email address']) ? $record['Email address'] : null,
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
            $userData = [
                'email' => $record['Email address'],
                'mobile' => $record['Phone'],
                'first_name' => $record['First name'],
                'last_name' => $record['Last name'],
                'addr_street1' => $record['Home address'],
                'addr_street2' => $record['Home unit'],
                'addr_city' => $record['Home city'],
                'addr_state' => $record['Home state'],
                'addr_zip' => $record['Home zip code'],
                'source' => env('NORTHSTAR_CLIENT_ID'),
            ];

            if ($record['Phone']) {
                $userData['sms_status'] = $this->transformSmsStatus($record['Opt-in to Partner SMS/robocall']);
            }

            $user = gateway('northstar')->asClient()->createUser($userData);

            if ($user->id) {
                info('created user', ['user' => $user->id]);
            } else {
                throw new HttpException(500, "Unable to create user: $this->record['Email address']");
            }
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
            'Tracking Source',
            'Started registration',
            'Finish with State',
            'Status',
            'Email address',
            'Home zip code'
        ];

        foreach ($importantKeys as $key) {
            $details[$key] = $record[$key];
        }

        if ($extraData) {
            $details = array_merge($details, $extraData);
        }

        return json_encode($details);
    }

    /**
     * Translate a status from Rock The Vote into a status that can be sent to Rogue.
     *
     * @param  string $rtvStatus
     * @param  string $rtvFinishWithState
     * @return string
     */
    private function translateStatus($rtvStatus, $rtvFinishWithState)
    {
        $rtvStatus = strtolower($rtvStatus);
        $rtvFinishWithState = strtolower($rtvFinishWithState);

        if($rtvStatus === 'complete') {
            if ($rtvFinishWithState === "no") {
                return 'register-form';
            }
            if ($rtvFinishWithState === "yes") {
                return 'register-OVR';
            }
        }

        if (strpos($rtvStatus, 'step') !== false) {
            return 'uncertain';
        }

        if ($rtvStatus === 'rejected') {
            return 'ineligible';
        }

        return '';
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


    /*
     * Translate "Opt-in to Partner SMS/robocall" from Rock the Vote CSV to a Northstar sms_status
     *
     * @param array $sms_status
     * @return string
    */
    private function transformSmsStatus($sms_status)
    {
        if ($sms_status === 'Yes') {
            return 'active';
        }

        // @TODO: do we want this to be 'pending' or some other status? we talked about this recently referring to something else
        return 'stop';
    }

    /*
     * Translate to Northstar status and update Northstar user (Northstar takes care of the hierarchy)
     *
     * @param Object $user
     * @param string $statusToSend
    */
    private function updateNorthstarStatus($user, $statusToSend)
    {
        if ($statusToSend === 'register-form' || $statusToSend === 'register-OVR') {
            $statusToSend = 'registration_complete';
        }

        try {
            gateway('northstar')->asClient()->updateUser($user->id, ['voter_registration_status' => $statusToSend]);
        } catch (\Exception $e) {
            info('Error updating voter_registration_status for user: ' . $user->id, [
                'Error' => $e->getMessage(),
            ]);
        }
    }
}