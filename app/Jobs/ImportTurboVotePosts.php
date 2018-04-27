<?php

namespace App\Jobs;


use App\Stat;
use Carbon\Carbon;
use League\Csv\Reader;
use App\Services\Rogue;
use App\Events\LogProgress;
use Illuminate\Bus\Queueable;
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
     * The path to the stored csv.
     *
     * @var array
     */
    protected $stats;

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
        return ['turbovote'];
    }

    /**
     * Execute the job.
     *
     * @param  Rogue $rogue
     * @return void
     */
    public function handle(Rogue $rogue)
    {
        $records = $this->getCSVRecords($this->filepath);

        foreach ($records as $record)
        {
            $shouldProcess = $this->scrubRecord($record);

            if ($shouldProcess) {
                info('progress_log: Processing: ' . $record['id']);

                $referralCode = $record['referral-code'];

                if (! empty($referralCode)) {
                    info("referral code not empty");
                    $this->stats['countHasReferralCode']++;
                    $referralCodeValues = $this->parseReferralCode(explode(',', $referralCode));

                    try {
                        $user = $this->getOrCreateUser($record, $referralCodeValues);
                        info("something should happen with the user", ['user' => $user->id]);
                    } catch (\Exception $e) {
                        info('There was an error with that user: ' . $record['id'], [
                            'Error' => $e->getMessage(),
                        ]);
                    }

                    if ($user) {
                        // @TODO - We probably don't need to do this for new users and can skip this.
                        $post = $rogue->getPost([
                                'campaign_id' => (int) $referralCodeValues['campaign_id'],
                                'northstar_id' => $user->id,
                                'type' => 'voter-reg',
                        ]);

                        if (! $post['data']) {
                            $tvCreatedAtMonth = strtolower(Carbon::parse($record['created-at'])->format('F-Y'));
                            $sourceDetails = isset($referralCodeValues['source_details']) ? $referralCodeValues['source_details'] : null;
                            $postDetails = $this->extractDetails($record);

                            $postData = [
                                'campaign_id' => (int) $referralCodeValues['campaign_id'],
                                'campaign_run_id' => (int) $referralCodeValues['campaign_run_id'],
                                'northstar_id' => $user->id,
                                'type' => 'voter-reg',
                                'action' => $tvCreatedAtMonth . '-turbovote',
                                'status' => $this->translateStatus($record['voter-registration-status'], $record['voter-registration-method']),
                                'source_details' => $sourceDetails,
                                'details' => $postDetails,
                            ];

                            try {
                                $post = $rogue->createPost($postData);

                                if ($post['data']) {
                                    $this->stats['countPostCreated']++;
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
                                    $rogue->updatePost($post['data'][0]['id'], ['status' => $statusShouldChange]);
                                } catch (\Exception $e) {
                                    info('There was an error updating the post for: ' . $record['id'], [
                                        'Error' => $e->getMessage(),
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    $this->stats['countMissingReferralCode']++;
                }
                $this->stats['countProcessed']++;
            } else {
                $this->stats['countScrubbed']++;
            }
        }

        event(new LogProgress('Done!'));

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
            'countMissingNSId' => 0,
            'countPostCreated' => 0,
            'countHasNorthstarID' => 0,
            'countHasReferralCode' => 0,
            'countMissingReferralCode' => 0,
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
        if (isset($values['northstar_id']) && !empty($values['northstar_id'])) {
            $this->stats['countHasNorthstarID']++;
            info('getting with id', ['id' => $values['northstar_id']]);
            $user = gateway('northstar')->asClient()->getUser('id', $values['northstar_id']);
        } elseif (isset($record['email']) &&  !empty($record['email'])) {
            $this->stats['countMissingNSId']++;
            info('getting with email', ['email' => $record['email']]);
            $user = gateway('northstar')->asClient()->getUser('email', $record['email']);
        } elseif (isset($record['phone']) && !empty($record['phone']) ) {
            info('getting with phone', ['mobil' => $record['phone']]);
            $this->stats['countMissingNSId']++;
            $user = gateway('northstar')->asClient()->getUser('mobile', $record['phone']);
        }

        info('this is the user before we decide to make one', ['user' => $user, 'evaluates' => is_null($user)]);
        if (is_null($user)) {
            info('creating user');
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

        return $user ? $user : null;
    }

    private function getCSVRecords($filepath)
    {
        $file = Storage::get($filepath);
        $csv = Reader::createFromString($file);
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        event(new LogProgress('Total rows to chomp: ' . count($csv)));
        $this->stats['totalRecords'] = count($csv);

        return $records;
    }
}

