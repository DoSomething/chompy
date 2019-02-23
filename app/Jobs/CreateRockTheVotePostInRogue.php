<?php

namespace Chompy\Jobs;

use Exception;
use Carbon\Carbon;
use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Chompy\Traits\ImportToRogue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Str;

class RockTheVoteRecord {
    public function __construct($record)
    {
        $this->addr_street1 = $record['Home address'];
        $this->addr_street2 = $record['Home unit'];
        $this->addr_city = $record['Home city'];
        $this->addr_state = $record['Home state'];
        $this->addr_zip = $record['Home zip code'];
        $this->email = $record['Email address'];
        $this->first_name = $record['First name'];
        $this->last_name = $record['Last name'];
        $this->mobile = $record['Phone'];

        $emailOptIn = $record['Opt-in to Partner email?'];
        if ($emailOptIn) {
            $this->email_subscription_status = str_to_boolean($emailOptIn);
        }
        // Note: Not a typo, this column name does not have the trailing question mark.
        $smsOptIn = $record['Opt-in to Partner SMS/robocall'];
        if ($smsOptIn && $this->mobile) {
            $this->sms_status = str_to_boolean($smsOptIn) ? 'active' : 'stop';
        }

        $rtvStatus = $this->parseVoterRegistrationStatus($record['Status'], $record['Finish with State']);
       
        $this->voter_registration_status =  Str::contains($rtvStatus, 'register') ? 'registration_complete' : $rtvStatus;

        $referral = $this->parseReferralCode($record['Tracking Source']);
        $this->user_id = !empty($referral['user_id']) ? $referral['user_id'] : null;
        // TODO: Do we need to check the referral for these values? Don't seem they are ever used.
        $this->campaign_id = 8017;
        $this->post_source = 'rock-the-vote';
        $this->post_source_details = null;
        $this->post_details = $this->parsePostDetails($record);
        $this->post_status = $rtvStatus;
        $this->post_type = 'voter-reg';
        $this->post_action = strtolower(Carbon::parse($record['Started registration'])->format('F-Y')) . '-rockthevote';
    }

    /**
     * Parse key values from referral code string.
     *
     * @param  string $referralCode
     * @return array
     */
    private function parseReferralCode($referralCode)
    {
        $values = [];
        info('Parsing referral code: ' . $referralCode);

        // Remove some nonsense that comes in front of the referral code sometimes
        if (strrpos($referralCode, 'iframe?r=') !== false) {
            $referralCode = str_replace('iframe?r=', null, $referralCode);
        }
        if (strrpos($referralCode, 'iframe?') !== false) {
            $referralCode = str_replace('iframe?', null, $referralCode);
        }

        if (empty($referralCode)) {
            return $values;
        }

        $referralCode = explode(',', $referralCode);

        foreach ($referralCode as $value) {
            // See if we are dealing with ":" or "="
            if (strpos($value, ':')) {
                $value = explode(':', $value);
            }
            elseif (strpos($value, '=')) {
                $value = explode('=', $value);
            }
            $key = strtolower($value[0]);
            if ($key === 'user') {
                $values['user_id'] = $value[1];
            }
            if (($key === 'campaignid' || $key === 'campaign') && is_numeric($value[1])) {
                $values['campaign_id'] = (int) $value[1];
            }
        }

        return $values;
    }

    /**
     * Translate a status from Rock The Vote into a Rogue post status.
     *
     * @param  string $rtvStatus
     * @param  string $rtvFinishWithState
     * @return string
     */
    private function parseVoterRegistrationStatus($rtvStatus, $rtvFinishWithState)
    {
        $rtvStatus = strtolower($rtvStatus);

        if ($rtvStatus === 'complete') {
            return str_to_boolean($rtvFinishWithState) ? 'register-OVR' : 'register-form';
        }

        if (str_contains($rtvStatus, 'step') !== false) {
            return 'uncertain';
        }

        if ($rtvStatus === 'rejected' || $rtvStatus === 'under 18') {
            return 'ineligible';
        }

        return '';
    }

    /**
     * Parse the record for extra details and return them as a JSON object.
     *
     * @param  array $record
     * @return string
     */
    private function parsePostDetails($record)
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

        return json_encode($details);
    }
}

class CreateRockTheVotePostInRogue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ImportToRogue;

    /**
     * The record parsed from a Rock the Vote csv.
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
        $this->record = new RockTheVoteRecord($record);
    }

    /**
     * Execute the job to create a Rock The Vote post in Rogue.
     *
     * @return array
     */
    public function handle(Rogue $rogue)
    {
        if (is_test_email($this->record->email)) {
            info('progress_log: Skipping test: ' . $this->record->email);
            return;
        }

        info('progress_log: Processing: ' . $this->record->email);

        $user = $this->getUser($this->record);
        if ($user && $user->id) {
            $this->updateUser($user, ['voter_registration_status' => $this->record->voter_registration_status]);
        } else {
            $user = $this->createUser($this->record);
        }

        $existingPosts = $rogue->getPost([
            'campaign_id' => $this->record->campaign_id,
            'northstar_id' => $user->id,
            'type' => $this->record->post_type,
        ]);

        if (!$existingPosts['data']) {
            $post = $rogue->createPost([
                'campaign_id' => $this->record->campaign_id,
                'northstar_id' => $user->id,
                'type' => $this->record->post_type,
                'action' => $this->record->post_action,
                'status' => $this->record->post_status,
                'source' => $this->record->post_source,
                'source_details' => $this->record->post_source_details,
                'details' => $this->record->post_details,
            ]);
            info('Created post', ['user' => $user->id]);
            return;
        }

        $post = $existingPosts['data'][0];
        info('Found post', ['post' => $post['id'], 'user' => $user->id]);

        $newStatus = $this->getVoterRegistrationStatusChange($post['status'], $this->record->post_status);
        if ($newStatus) {
            $rogue->updatePost($post['id'], ['status' => $newStatus]);
        }
    }

    /**
     * Check for user first by record user_id, next by email, last by mobile.
     *
     * @param array $record
     * @return NorthstarUser
     */
    private function getUser($record)
    {
        if ($record->user_id) {
            $user = gateway('northstar')->asClient()->getUser('id', $record->user_id);
            if ($user && $user->id) {
                return $user;
            }
        }
        if ($record->email) {
            $user = gateway('northstar')->asClient()->getUser('email', $record->email);
            if ($user && $user->id) {
                return $user;
            }
        }
        if (!$record->mobile) {
            return null;
        }
        return gateway('northstar')->asClient()->getUser('mobile', $record->mobile);
    }

    /**
     * Creates new user from record.
     *
     * @param array $record
     * @return NorthstarUser
     */
    private function createUser($record)
    {
        $userData = [];

        $userFields = ['addr_city', 'addr_state', 'addr_street1', 'addr_street2', 'addr_zip', 'email', 'mobile', 'first_name', 'last_name', 'voter_registration_status'];

        foreach ($userFields as $key) {
            $userData[$key] = $record->{$key};
        }

        $userData['source'] = config('services.northstar.client_credentials.client_id');
    
        if (!empty($record->email_subscription_status)) {
            $userData['email_subscription_status'] = $record->email_subscription_status;
        }

        if (!empty($record->sms_status)) {
            $userData['sms_status'] = $record->sms_status;
        }

        $user = gateway('northstar')->asClient()->createUser($userData);

        if (!$user->id) {
            throw new Exception(500, 'Unable to create user: ' . $record->email);
        }
        info('Created user', ['user' => $user->id]);

        return $user;
    }

    /**
     * Determines if a status should be changed and what it should be changed to.
     *
     * @param string $currentStatus
     * @param string $newStatus
     * @return string|null
     */
    private function getVoterRegistrationStatusChange($currentStatus, $newStatus)
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

    /**
     * Update Northstar user with given data.
     *
     * @param Object $user
     * @param Array $data
     */
    private function updateUser($user, $data)
    {
        gateway('northstar')->asClient()->updateUser($user->id, $data);
        info('Updated user', ['user' => $user->id]);
    }
}
