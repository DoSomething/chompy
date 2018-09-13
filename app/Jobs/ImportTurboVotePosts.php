<?php

namespace Chompy\Jobs;

use Chompy\Stat;
use League\Csv\Reader;
use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Chompy\Events\LogProgress;
use Chompy\Traits\ImportToRogue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Chompy\Jobs\CreateTurboVotePostInRogue;

class ImportTurboVotePosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ImportToRogue;

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
        // @TODO: We need to write some tests for this import!
        info('getting records');

        $records = $this->getCSVRecords($this->filepath);

        info("records received");

        foreach ($records as $offset => $record)
        {
            $shouldProcess = $this->scrubRecord($record);

            if ($shouldProcess) {
                info('progress_log: Processing: ' . $record['id']);

                $referralCode = $record['referral-code'];
                $referralCodeValues = $this->parseReferralCode($referralCode);

                try {
                    $user = $this->getOrCreateUser($record, $referralCodeValues);
                } catch (\Exception $e) {
                    info('There was an error with that user: ' . $record['id'], [
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
                        CreateTurboVotePostInRogue::dispatch($record, $referralCodeValues, $user);
                    } else {
                        $newStatus = $this->translateTVStatus($record['voter-registration-status'], $record['voter-registration-method']);
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
     * Initiate the stat counters to use with imports to Rogue.
     *
     * @return array
     */
    public function statsInit()
    {
        return [
            'totalRecords' => 0,
            'countScrubbed' => 0,
            'countProcessed' => 0,
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
            $this->stats['countUserAccountsCreated']++;
        }

        return $user;
    }
}
