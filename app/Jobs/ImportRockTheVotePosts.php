<?php

namespace Chompy\Jobs;

use Chompy\Stat;
use Carbon\Carbon;
use League\Csv\Reader;
use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Chompy\Events\LogProgress;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportRockTheVotePosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The path to the stored csv.
     *
     * @var string
     */
    protected $filepath;

    /**
     * The path to the stored csv.
     *
     * @var array
     */
    protected $stats;

    protected $totalRecords;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($filepath)
    {
        $this->filepath = $filepath;

        $this->stats = $this->statsInit();
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags()
    {
        return ['rock-the-vote'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Rogue $rogue)
    {
        info('STARTING RTV IMPORT');

        $records = $this->getCSVRecords($this->filepath);

        foreach ($records as $offset => $record)
        {
            $shouldProcess = $this->scrubRecord($record);

            if ($shouldProcess) {
                info('progress_log: Processing: ' . $record['Email address']);

                $referralCode = $record['Tracking Source'];
                $referralCodeValues = $this->parseReferralCode($referralCode);

                try {
                    $user = $this->getOrCreateUser($record, $referralCodeValues);
                } catch (\Exception $e) {
                    info('There was an error with that user: ' . $record['Email address'], [
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
                        $rtvCreatedAtMonth = strtolower(Carbon::parse($record['Started registration'])->format('F-Y'));
                        $sourceDetails = isset($referralCodeValues['source_details']) ? $referralCodeValues['source_details'] : null;
                        $postDetails = $this->extractDetails($record);

                        $postData = [
                            'campaign_id' => (int) $referralCodeValues['campaign_id'],
                            'campaign_run_id' => (int) $referralCodeValues['campaign_run_id'],
                            'northstar_id' => $user->id,
                            'type' => 'voter-reg',
                            'action' => $rtvCreatedAtMonth . '-rockthevote',
                            'status' => $this->translateStatus($record['Status'], $record['Finish with State']),
                            'source' => 'rock-the-vote',
                            'source_details' => $sourceDetails,
                            'details' => $postDetails,
                        ];

                        try {
                            $post = $rogue->createPost($postData);

                            if ($post['data']) {
                                $this->stats['countPostCreated']++;
                            }
                        } catch (\Exception $e) {
                            info('There was an error storing the post', [
                                'Error' => $e->getMessage(),
                            ]);
                        }
                    } else {
                        $newStatus = $this->translateStatus($record['Status'], $record['Finish with State']);
                        $statusShouldChange = $this->updateStatus($post['data'][0]['status'], $newStatus);

                        if ($statusShouldChange) {
                            try {
                                $rogue->updatePost($post['data'][0]['id'], ['status' => $statusShouldChange]);
                            } catch (\Exception $e) {
                                info('There was an error updating the post for: ' . $record['Email address'], [
                                    'Error' => $e->getMessage(),
                                ]);
                            }
                        }
                    }
                }
                $this->stats['countProcessed']++;
            } else {
                $this->stats['countScrubbed']++;
            }

            event(new LogProgress('', 'progress', ($offset / $this->totalRecords) * 100));
        }

        event(new LogProgress('Done!', 'general'));

        Stat::create([
            'filename' => $this->filepath,
            'total_records' => $this->stats['totalRecords'],
            'stats' => json_encode($this->stats),
        ]);

    }

    /**
     * Initiate the stat counters.
     *
     * @return array
     */
    private function statsInit()
    {
        return [
            'totalRecords' => 0,
            'countScrubbed' => 0,
            'countProcessed' => 0,
            'countPostCreated' => 0,
            'countUserAccountsCreated' => 0,
        ];
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

                // Is this a referral?
                if (strtolower($value[0]) === 'referral' && strtolower($value[1]) === 'true') {
                    $referral = true;
                }
            }
        }

        // See if we have all the required information we need
        if (!array_has($values, ['northstar_id', 'campaign_id', 'campaign_run_id'])) {
            // If we have the campaign values, use em! This also means that we do not have NS id
            if (array_has($values, ['campaign_id', 'campaign_run_id'])) {
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

            info('created user', ['user' => $user->id]);
            $this->stats['countUserAccountsCreated']++;
        }

        return $user;
    }

    private function getCSVRecords($filepath)
    {
        $file = Storage::get($filepath);
        $csv = Reader::createFromString($file);
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();
        $this->totalRecords = count($csv);

        event(new LogProgress('Total rows to chomp: ' . $this->totalRecords, 'general'));
        event(new LogProgress('', 'progress', 0));

        $this->stats['totalRecords'] = $this->totalRecords;

        return $records;
    }
}
