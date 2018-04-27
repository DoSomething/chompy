<?php

namespace App\Jobs;


use App\Stat;
use Carbon\Carbon;
use League\Csv\Reader;
use App\Services\Rogue;
use App\Events\LogProgress;
use Illuminate\Bus\Queueable;
// use DoSomething\Gateway\Northstar;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportTurboVotePosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The path to the stored csv.
     *
     * @var string
     */
    protected $filepath;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($filepath)
    {
        $this->filepath = $filepath;
    }


    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags()
    {
        return ['turbovote'];
    }

    public function handle(Rogue $rogue)
    {
        $file = Storage::get($this->filepath);
        $csv = Reader::createFromString($file);
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        event(new LogProgress('Total rows to chomp: '.count($csv)));

        // Metrics
        $totalRecords = count($csv);
        $countScrubbed = 0;
        $countProcessed = 0;
        $countMissingNSId = 0;
        $countPostCreated = 0;
        $countHasNorthstarID = 0;
        $countHasReferralCode = 0;
        $countNSAccountCreated = 0;
        $countMissingReferralCode = 0;

        foreach($records as $record)
        {
            $shouldProcess = $this->scrubRecord($record);

            if ($shouldProcess)
            {
                $countProcessed++;
                $referralCode = $record['referral-code'];
                // event(new LogProgress('Processing record: ' . $record['id']));
                info('progress_log: Processing: '. $record['id']);

                if (! empty($referralCode)) {
                    $countHasReferralCode++;
                    $referralCodeValues = $this->parseReferralCode(explode(',', $referralCode));

                    // Fall back to the Grab The Mic campaign (campaign_id: 8017, campaign_run_id: 8022)
                    // if these keys are not present.
                    // @TODO - make sure these go together
                    $referralCodeValues['campaign_id'] = !isset($referralCodeValues['campaign_id']) ? 8017 : $referralCodeValues['campaign_id'];
                    $referralCodeValues['campaign_run_id'] = !isset($referralCodeValues['campaign_run_id']) ? 8022 : $referralCodeValues['campaign_run_id'];

                    if (isset($referralCodeValues['northstar_id'])) {
                        $countHasNorthstarID++;

                        $post = $rogue->asClient()->get('v3/posts', [
                            'filter' => [
                                'campaign_id' => (int) $referralCodeValues['campaign_id'],
                                'northstar_id' => $referralCodeValues['northstar_id'],
                                'type' => 'voter-reg',
                            ]
                        ]);

                        if (! $post['data']) {
                            $tvCreatedAtMonth = strtolower(Carbon::parse($record['created-at'])->format('F-Y'));
                            $sourceDetails = isset($referralCodeValues['source_details']) ? $referralCodeValues['source_details'] : null;
                            $postDetails = $this->extractDetails($record);

                            $postData = [
                                'campaign_id' => (int) $referralCodeValues['campaign_id'],
                                'campaign_run_id' => (int) $referralCodeValues['campaign_run_id'],
                                'northstar_id' => $referralCodeValues['northstar_id'],
                                'type' => 'voter-reg',
                                'action' => $tvCreatedAtMonth . '-turbovote',
                                'status' => $this->translateStatus($record['voter-registration-status'], $record['voter-registration-method']),
                                'source_details' => $sourceDetails,
                                'details' => $postDetails,
                            ];

                            $multipartData = collect($postData)->map(function ($value, $key) {
                                return ['name' => $key, 'contents' => $value];
                            })->values()->toArray();

                            try {
                                $roguePost = $rogue->asClient()->send('POST', 'v3/posts', ['multipart' => $multipartData]);

                                if ($roguePost['data']) {
                                    $countPostCreated++;
                                }
                            } catch (\Exception $e) {
                                info('There was an error storing the post for: ' . $record['id'], [
                                    'Error' => $e->getMessage(),
                                ]);
                            }
                        } else {
                            $newStatus = $this->translateStatus($record['voter-registration-status'], $record['voter-registration-method']);
                            $statusShouldChange = $this->updateStatus($post['data'][0]['status'], $newStatus);

                            if ($statusShouldChange) {
                                try {
                                    $roguePost = $rogue->asClient()->patch('v3/posts/'.$post['data'][0]['id'], [
                                        'status' => $statusShouldChange,
                                    ]);
                                } catch (\Exception $e) {
                                    info('There was an error updating the post for: ' . $record['id'], [
                                        'Error' => $e->getMessage(),
                                    ]);
                                }
                            }
                        }
                    } else {
                        $countMissingNSId++;

                        try {
                            // Check if user exists already using ns id or mobile number.
                            // @TODO - make into funtion.
                            if (isset($record['email'])) {
                                $existingUser = gateway('northstar')->asClient()->getUser('email', $record['email']);
                            } elseif (isset($record['phone'])) {
                                $existingUser = gateway('northstar')->asClient()->getUser('mobile', $record['phone']);
                            } else {
                                $existingUser = null;
                            }

                            // If they don't exist, create a ns account for them
                            if (! $existingUser) {
                                $newNorthstarUser = gateway('northstar')->asClient()->createUser([
                                    'email' => $record['email'],
                                    'mobile' => $record['phone'],
                                    'first_name' => $record['first-name'],
                                    'last_name' => $record['last-name'],
                                    'addr_stree1' => $record['registered-address-street'],
                                    'addr_stree2' => $record['registered-address-street-2'],
                                    'addr_city' => $record['registered-address-city'],
                                    'addr_state' => $record['registered-address-state'],
                                    'addr_zip' => $record['registered-address-zip'],
                                ]);
                            }


                            // if they do, grab the account, check if they have a voter-reg post already
                            $user = $existingUser->id ? $existingUser : $newNorthstarUser;

                            $post = $rogue->asClient()->get('v3/posts', [
                                'filter' => [
                                    'campaign_id' => (int) $referralCodeValues['campaign_id'],
                                    'northstar_id' => $user->id,
                                    'type' => 'voter-reg',
                                ]
                            ]);

                            if (!$post['data']) {
                                $tvCreatedAtMonth = strtolower(Carbon::parse($record['created-at'])->format('F-Y'));
                                $sourceDetails = isset($referralCodeValues['source_details']) ? $referralCodeValues['source_details'] : null;
                                $postDetails = $this->extractDetails($record);

                                $postData = [
                                    'campaign_id' => (int)$referralCodeValues['campaign_id'],
                                    'campaign_run_id' => (int)$referralCodeValues['campaign_run_id'],
                                    'northstar_id' => $user->id,
                                    'type' => 'voter-reg',
                                    'action' => $tvCreatedAtMonth . '-turbovote',
                                    'status' => $this->translateStatus($record['voter-registration-status'], $record['voter-registration-method']),
                                    'source_details' => $sourceDetails,
                                    'details' => $postDetails,
                                ];

                                $multipartData = collect($postData)->map(function ($value, $key) {
                                    return ['name' => $key, 'contents' => $value];
                                })->values()->toArray();

                                try {
                                    $roguePost = $rogue->asClient()->send('POST', 'v3/posts', ['multipart' => $multipartData]);

                                    if ($roguePost['data']) {
                                        $countPostCreated++;
                                    }
                                } catch (\Exception $e) {
                                    info('There was an error storing the post for: ' . $record['id'], [
                                        'Error' => $e->getMessage(),
                                    ]);
                                }
                            } else {
                                $newStatus = $this->translateStatus($record['voter-registration-status'], $record['voter-registration-method']);
                                $statusShouldChange = $this->updateStatus($post['data'][0]['status'], $newStatus);

                                if ($statusShouldChange) {
                                    try {
                                        $roguePost = $rogue->asClient()->patch('v3/posts/' . $post['data'][0]['id'], [
                                            'status' => $statusShouldChange,
                                        ]);
                                    } catch (\Exception $e) {
                                        info('There was an error updating the post for: ' . $record['id'], [
                                            'Error' => $e->getMessage(),
                                        ]);
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            info('There was an error creating a NS account for: ' . $record['id'], [
                                'Error' => $e->getMessage(),
                            ]);
                        }

                        if ($newNorthstarUser) {
                            $countNSAccountCreated++;

                            info('New NS user created or updated: ', [
                                'record' => $record['id'],
                                'ns_id' => $newNorthstarUser->id,
                            ]);
                        }
                    }
                } else {
                    $countMissingReferralCode++;
                }
            } else {
                $countScrubbed++;
            }
        }

        event(new LogProgress('Done!'));

        $stat = Stat::create([
            'filename' => $this->filepath,
            'total_records' => $totalRecords,
            'stats' => json_encode([
                'processed' => $countProcessed,
                'scrubbed' => $countScrubbed,
                'has_referral_codes' => $countHasReferralCode,
                'missing_referral_code' => $countMissingReferralCode,
                'has_northstar_id' => $countHasNorthstarID,
                'missing_northstar_id' => $countMissingNSId,
                'posts_created' => $countPostCreated,
                'northstar_account_created' => $countNSAccountCreated,
            ]),
        ]);
    }

    /**
     * Parse the referral code field to grab individual values.
     *
     * @param  array $refferalCode
     */
    private function parseReferralCode($referralCode)
    {
        $values = [];

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

        return $values;
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
            $details[$key] = $record[$key];
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
    private function translateStatus($tvStatus, $tvMethod)
    {
        if (!$tvStatus || !$tvMethod)
        {
            // @TODO - Throw error.
        }

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

    /*
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
}

