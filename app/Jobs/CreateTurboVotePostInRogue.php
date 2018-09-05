<?php

namespace Chompy\Jobs;

use Carbon\Carbon;
use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateTurboVotePostInRogue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The record to be created into a post from the csv.
     *
     * @var array
     */
    protected $record;

    /**
     * The referral code values to translate to Rogue post.
     *
     * @var array
     */
    protected $referralCodeValues;

    /**
     * The user that owns the post.
     *
     * @var object
     */
    protected $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($record, $referralCodeValues, $user)
    {
    	$this->record = $record;

    	$this->referralCodeValues = $referralCodeValues;

    	$this->user = $user;
    }

    /**
     * Execute the job to create a Turbo Vote post in Rogue.
     *
     * @return array
     */
    public function handle(Rogue $rogue)
    {
        $tvCreatedAtMonth = strtolower(Carbon::parse($this->record['created-at'])->format('F-Y'));
        $sourceDetails = isset($this->referralCodeValues['source_details']) ? $referralCodeValues['source_details'] : null;
        $postDetails = $this->extractDetails($this->record);

        $postData = [
            'campaign_id' => (int) $this->referralCodeValues['campaign_id'],
            'campaign_run_id' => (int) $this->referralCodeValues['campaign_run_id'],
            'northstar_id' => $this->user->id,
            'type' => 'voter-reg',
            'action' => $tvCreatedAtMonth . '-turbovote',
            'status' => $this->translateStatus($this->record['voter-registration-status'], $this->record['voter-registration-method']),
            'source' => 'turbovote',
            'source_details' => $sourceDetails,
            'details' => $postDetails,
        ];

        try {
            $post = $rogue->createPost($postData);

            if ($post['data']) {
                return true;
            }
    	} catch (\Exception $e) {
                            info('There was an error storing the post for: ' . $this->record['id'], [
                                'Error' => $e->getMessage(),
                            ]);
                        }
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

}