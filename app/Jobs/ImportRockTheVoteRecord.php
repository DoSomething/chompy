<?php

namespace Chompy\Jobs;

use Exception;
use Chompy\ImportType;
use Chompy\Services\Rogue;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RockTheVoteRecord
{
    /**
     * Parses values to send to DS API from given CSV record, using given config.
     *
     * @param array $record
     * @param array $config
     */
    public function __construct($record, $config)
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
            if ($this->email_subscription_status) {
                $this->email_subscription_topics = explode(',', $config['user']['email_subscription_topics']);
            }
        }
        // Note: Not a typo, this column name does not have the trailing question mark.
        $smsOptIn = $record['Opt-in to Partner SMS/robocall'];
        if ($smsOptIn && $this->mobile) {
            $this->sms_status = str_to_boolean($smsOptIn) ? 'active' : 'stop';
        }
        $rtvStatus = $this->parseVoterRegistrationStatus($record['Status'], $record['Finish with State']);

        $this->voter_registration_status = Str::contains($rtvStatus, 'register') ? 'registration_complete' : $rtvStatus;

        $this->user_id = $this->parseUserId($record['Tracking Source']);

        $postConfig = $config['post'];
        $this->post_source = $postConfig['source'];
        $this->post_source_details = null;
        $this->post_details = $this->parsePostDetails($record);
        $this->post_status = $rtvStatus;
        $this->post_type = $postConfig['type'];
        $this->post_action_id = $postConfig['action_id'];
    }

    /**
     * Parse existing user ID from referral code string.
     *
     * @param  string $referralCode
     * @return string
     */
    private function parseUserId($referralCode)
    {
        info('Parsing referral code: ' . $referralCode);

        // Remove some nonsense that comes in front of the referral code sometimes
        if (str_contains($referralCode, 'iframe?r=')) {
            $referralCode = str_replace('iframe?r=', null, $referralCode);
        }
        if (str_contains($referralCode, 'iframe?')) {
            $referralCode = str_replace('iframe?', null, $referralCode);
        }

        if (empty($referralCode)) {
            return null;
        }

        $referralCode = explode(',', $referralCode);

        foreach ($referralCode as $value) {
            // See if we are dealing with ":" or "="
            if (str_contains($value, ':')) {
                $value = explode(':', $value);
            } elseif (str_contains($value, '=')) {
                $value = explode('=', $value);
            }

            $key = strtolower($value[0]);
            // We expect 'user', but check for any variations/typos in any manually entered URLs.
            if ($key === 'user' || $key === 'user_id' || $key === 'userid') {
                $userId = $value[1];
            }

            /**
             * If referral parameter is set to true, the user parameter belongs to the referring
             * user, not the user that should be associated with this voter registration record.
             */
            // We expect 'referral', but check for any typos in any manually entered URLs.
            if (($key === 'referral' || $key === 'refferal') && str_to_boolean($value[1])) {
                /**
                 * Return null to force querying for existing user via this record email or mobile
                 * upon import.
                 */
                return null;
            }
        }

        return isset($userId) ? $userId : null;
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

        if (str_contains($rtvStatus, 'step')) {
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
            'Home zip code',
        ];

        foreach ($importantKeys as $key) {
            $details[$key] = $record[$key];
        }

        return json_encode($details);
    }
}

class ImportRockTheVoteRecord implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

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
        $this->config = ImportType::getConfig(ImportType::$rockTheVote);
        $this->record = new RockTheVoteRecord($record, $this->config);
    }

    /**
     * Execute the job to create a Rock The Vote post in Rogue.
     *
     * @return array
     */
    public function handle(Rogue $rogue)
    {
        info('progress_log: Processing: ' . $this->record->email);

        $user = $this->getUser($this->record);
        if ($user && $user->id) {
            $newStatus = $this->getVoterRegistrationStatusChange($user->voter_registration_status, $this->record->voter_registration_status);

            if ($newStatus) {
                $this->updateUser($user, ['voter_registration_status' => $newStatus]);
            }
        } else {
            $user = $this->createUser($this->record);
            $this->sendUserPasswordResetIfSubscribed($user);
        }

        $existingPosts = $rogue->getPost([
            'action_id' => $this->record->post_action_id,
            'northstar_id' => $user->id,
        ]);

        if (! $existingPosts['data']) {
            $post = $rogue->createPost([
                'action_id' => $this->record->post_action_id,
                'northstar_id' => $user->id,
                'type' => $this->record->post_type,
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
        if (! $record->mobile) {
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

        if (isset($record->email_subscription_status)) {
            $userData['email_subscription_status'] = $record->email_subscription_status;
        }

        if (isset($record->email_subscription_topics)) {
            $userData['email_subscription_topics'] = $record->email_subscription_topics;
        }

        if (isset($record->sms_status)) {
            $userData['sms_status'] = $record->sms_status;
        }

        $user = gateway('northstar')->asClient()->createUser($userData);

        if (! $user->id) {
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
        // List includes status values expected from RTV as well as
        // values potentially assigned from within Northstar.
        $statusHierarchy = [
            'uncertain',
            'ineligible',
            'unregistered',
            'confirmed',
            'register-OVR',
            'register-form',
            'registration_complete',
        ];

        $indexOfCurrentStatus = array_search($currentStatus, $statusHierarchy);
        $indexOfNewStatus = array_search($newStatus, $statusHierarchy);

        return $indexOfCurrentStatus < $indexOfNewStatus ? $newStatus : null;
    }

    /**
     * Update Northstar user with given data.
     *
     * @param object $user
     * @param array $data
     */
    private function updateUser($user, $data)
    {
        gateway('northstar')->asClient()->updateUser($user->id, $data);
        info('Updated user', ['user' => $user->id]);
    }

    /**
     * Send Northstar user a password reset email.
     *
     * @param object $user
     */
    private function sendUserPasswordResetIfSubscribed($user)
    {
        /**
         * Our Customer.io event triggered campaign that sends these RTV password resets should be
         * configured to not send the email to an unsubscribed user, but let's sanity check anyway.
         */
        if (! $user->email_subscription_status) {
            info('Did not send email to unsubscribed user', ['user' => $user->id]);

            return;
        }

        $resetConfig = $this->config['reset'];
        $resetType = $resetConfig['type'];
        $logParams = ['user' => $user->id, 'type' => $resetType];

        if ($resetConfig['enabled'] !== 'true') {
            info('Reset email is disabled. Would have sent reset email', $logParams);

            return;
        }

        gateway('northstar')->asClient()->sendUserPasswordReset($user->id, $resetType);
        info('Sent reset email', $logParams);
    }
}
