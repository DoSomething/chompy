<?php

namespace App\Jobs;


use Carbon\Carbon;
use League\Csv\Reader;
use App\Services\Rogue;
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
     * Execute the job.
     *
     * @return void
     */
    public function handle(Rogue $rogue)
    {
        // @TODO - remove CSV from s3 when done.
        $file = Storage::get($this->filepath);
        $csv = Reader::createFromString($file);
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        foreach ($records as $record) {
            info('Importing record ' . $record['id']);

            $referralCode = $record['referral-code'];

            if ($referralCode) {
                $referralCodeValues = $this->parseReferralCode(explode(',', $referralCode));

                // Fall back to the Grab The Mic campaign (campaign_id: 8017, campaign_run_id: 8022)
                // if these keys are not present.
                $referralCodeValues['campaign_id'] = !isset($referralCodeValues['campaign_id']) ? 8017 : $referralCodeValues['campaign_id'];
                $referralCodeValues['campaign_run_id'] = !isset($referralCodeValues['campaign_run_id']) ? 8022 : $referralCodeValues['campaign_run_id'];

                if (isset($referralCodeValues['northstar_id'])) {
                    $tvCreatedAtMonth = strtolower(Carbon::parse($record['created-at'])->format('F-Y'));
                    $sourceDetails = isset($referralCodeValues['source_details']) ? $referralCodeValues['source_details'] : null;
                    $postDetails = $this->extractDetails($record);

                    $postData = [
                        'campaign_id' =>(int) $referralCodeValues['campaign_id'],
                        'campaign_run_id' => (int) $referralCodeValues['campaign_run_id'],
                        'northstar_id' => $referralCodeValues['northstar_id'],
                        'type' => 'voter-reg',
                        'action' => $tvCreatedAtMonth . '-turbovote',
                        'status' => 'pending',//$record['voter-registration-status'],
                        'source' => 'chompy',
                        'source_details' => $sourceDetails,
                        'details' => $postDetails,
                        'text' => 'This should not be required',
                        'quantity' => null,
                    ];

                    $multipartData = collect($postData)->map(function ($value, $key) {
                        return ['name' => $key, 'contents' => $value];
                    })->values()->toArray();

                    $rogue->asClient()->send('POST', 'v3/posts', ['multipart' => $multipartData]);

                    //  @TODO - somehow confirm the post was created. maybe more accurate errors returned so we can catch things easilt to retrigger the job.
                }
            }
        }
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
}

