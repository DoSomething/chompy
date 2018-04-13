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

        // Metrics
        $countProcessed = 0;
        $countHasReferralCode = 0;
        $countHasNorthstarID = 0;
        $countPostNeedsToBeCreated = 0;

        foreach($records as $record)
        {
            // make sure record should be processed.
            // $shouldProcess = $this->scrubRecord($record);

            $shouldProcess= true;

            if ($shouldProcess)
            {
                $countProcessed++;
                $referralCode = $record['referral-code'];
                event(new LogProgress('Processing record: ' . $record['id']));

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
                            $countPostNeedsToBeCreated++;

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

                            // @TODO - figure out what todo if this fails. move into try/catch
                            $roguePost = $rogue->asClient()->send('POST', 'v3/posts', ['multipart' => $multipartData]);
                        } else {
                            $newStatus = $this->translateStatus($record['voter-registration-status'], $record['voter-registration-method']);

                            $statusShouldChange = $this->updateStatus($post['data'][0]['status'], $newStatus);

                            if ($statusShouldChange) {
                                event(new LogProgress('Change status of post to: ' . $statusShouldChange));
                                // $roguePost = $rogue->asClient()->send('POST', 'v3/posts', ['multipart' => $multipartData]);
                            } else {
                                event(new LogProgress('Status stays the same'));
                            }
                        }
                    } else {
                        // Northstar ID does not exist
                        // @TODO - create NS account and process
                    }
                } else {
                    // No referral code...
                }
            } else {
                // record was cleaned and skipped
            }
        }

        event(new LogProgress('Done!'));
        event(new LogProgress('$countProcessed: '. $countProcessed));
        event(new LogProgress('$countHasReferralCode: ' . $countHasReferralCode));
        event(new LogProgress('$countHasNorthstarID: ' . $countHasNorthstarID));
        event(new LogProgress('$countPostNeedsToBeCreated: ' . $countPostNeedsToBeCreated));
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
        }

        return $translatedStatus;
    }

    private function updateStatus($currentStatus, $newStatus)
    {
        $statusHierarchy = [
            'uncertain',
            'ineligible',
            'confirmed',
            'register-OVR',
            'register-form',
        ];

        $changeStatus = null;

        $indexOfCurrentStatus = array_search($currentStatus, $statusHierarchy);
        $indexOfNewStatus = array_search($newStatus, $statusHierarchy);

        if ($indexOfCurrentStatus < $indexOfNewStatus)
        {
            $changeStatus = $newStatus;
        }

        return $changeStatus;
    }

    /*
        If an email includes thing.org in the address, ignore it.
        If a hostname includes `testing, ignore it.
        If an email includes @dosome in the address, ignore it.
        If a last name includes Baloney, ignore it.
        If an email includes turbovote, ignore it.
    */
    private function scrubRecord($record)
    {
        $scrub = false;

        $isValidEmail = strrpos($record['email'], 'thing.org') === false || strrpos($record['email'] === false, '@dosome') || strrpos($record['email'], 'turbovote') === false;
        $isValidHostname = strrpos($record['hostname'], 'testing') === false;
        $isValidLastName = strrpos($record['last-name'], 'Baloney') === false;

        if (!$isValidEmail || !$isValidHostname || !$isValidLastName)
        {
            $scrub = true;
        }

        return $scrub;
    }
}

