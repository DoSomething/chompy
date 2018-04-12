<?php

namespace App\Jobs;


use Carbon\Carbon;
use League\Csv\Reader;
use App\Services\Rogue;
use League\Csv\Statement;
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
        // Will createFromPath work here
        $file = Storage::get($this->filepath);
        $csv = Reader::createFromString($file);
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        event(new LogProgress('Total rows to chomp: '.count($csv)));

        foreach($records as $record)
        {
            // make sure record should be processed.
            // $shouldProcess = $this->scrubRecord($record);

            $shouldProcess= true;

            if ($shouldProcess)
            {
                /*
                 * MIGHT NOT NEED TO DO THIS SINCE ROGUE WILL CREATE THE SIGNUP AUTOMATICALLY IF IT DOESN'T EXIST.
                 * see if the signup already exists.
                 * if it doesn't, create the signup.
                 * Send the signup to rogue.
                 */
                $referralCode = $record['referral-code'];

                event(new LogProgress('Processing record: ' . $record['id']));

                if (! empty($referralCode)) {

                    $referralCodeValues = $this->parseReferralCode(explode(',', $referralCode));

                    // Fall back to the Grab The Mic campaign (campaign_id: 8017, campaign_run_id: 8022)
                    // if these keys are not present.
                    $referralCodeValues['campaign_id'] = !isset($referralCodeValues['campaign_id']) ? 8017 : $referralCodeValues['campaign_id'];
                    $referralCodeValues['campaign_run_id'] = !isset($referralCodeValues['campaign_run_id']) ? 8022 : $referralCodeValues['campaign_run_id'];

                    if (isset($referralCodeValues['northstar_id'])) {
                        // Check if the post exists in rogue already
                        $post = $rogue->asClient()->send('GET', 'v3/posts', [
                            'filter' => [
                                'campaign_id' =>(int) $referralCodeValues['campaign_id'],
                                'northstar_id' => $referralCodeValues['northstar_id'],
                                'type' => 'voter-reg',
                            ]
                        ]);

                        // If it doesn't, create the post with the provided status.
                        if (! $post['data']) {
                            event(new LogProgress('No post found, something needs to happen'));
                            // TODO - count new posts created.
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
                            dd('send this to rogue');
                            $roguePost = $rogue->asClient()->send('POST', 'v3/posts', ['multipart' => $multipartData]);
                            dd($roguePost);
                            event(new LogProgress('New Post Created: '. $record['id']));

                        } else {
                            event(new LogProgress('suggests the post exists already'));
                        }

                    } else {
                        // Northstar ID does not exist
                        event(new LogProgress('Northstar ID does not exist'));
                    }
                } else {
                    // count these
                    event(new LogProgress('referall code empty: '.$record['id']));
                }


                // dd($post);
                // If it does get that status and apply the hierarchy logic to know if it's status should be updated.
                // If it doesn't, create the post with the provided status.
                // send the post to rogue
            } else {
                // record was cleaned and skipped
            }
        }

        event(new LogProgress('Done!'));
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
            if (strtolower($value[0]) === 'campaignid') {
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
            case 'intiated':
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
        }

        return $translatedStatus;
    }
}

