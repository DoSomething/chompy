<?php

namespace Chompy\Jobs;

use Exception;
use Chompy\SmsStatus;
use Chompy\ImportType;
use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Chompy\RockTheVoteRecord;
use Chompy\Models\ImportFile;
use Chompy\Models\RockTheVoteLog;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DoSomething\Gateway\Resources\NorthstarUser;

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
    public function __construct($record, ImportFile $importFile)
    {
        $this->config = ImportType::getConfig(ImportType::$rockTheVote);
        $this->record = new RockTheVoteRecord($record, $this->config);
        $this->userData = $this->record->userData;
        $this->postData = $this->record->postData;
        $this->importFile = $importFile;
        $this->smsOptIn = isset($this->userData['sms_status']) && $this->userData['sms_status'] == SmsStatus::$active;
    }

    /**
     * Execute the job to create a Rock The Vote post in Rogue.
     *
     * @return array
     */
    public function handle(Rogue $rogue)
    {
        info('progress_log: Processing Rock The Vote record');

        $user = $this->getUser($this->userData['id'], $this->userData['email'], $this->userData['mobile']);

        if (! $user) {
            $user = $this->createUser();

            $this->createPost($user);

            RockTheVoteLog::createFromRecord($this->record, $user, $this->importFile);

            $this->sendUserPasswordResetIfSubscribed($user);

            return;
        }

        if (RockTheVoteLog::getByRecord($this->record, $user)) {
            $details = $this->record->getPostDetails();

            info('Skipping record that has already been imported', [
                'user' => $user->id,
                'status' => $details['Status'],
                'started_registration' => $details['Started registration'],
            ]);

            $this->importFile->incrementSkipCount();

            return;
        }

        $this->updateUserIfChanged($user);

        if ($post = $this->getPost($user)) {
            $this->updatePostIfChanged($post);
        } else {
            $this->createPost($user);
        }

        RockTheVoteLog::createFromRecord($this->record, $user, $this->importFile);
    }

    /**
     * Check for user first by id, next by email, last by mobile.
     *
     * @param string $id
     * @param string $email
     * @param string $mobile
     * @return NorthstarUser
     */
    private function getUser($id, $email, $mobile)
    {
        if ($id) {
            $user = gateway('northstar')->asClient()->getUser('id', $id);

            if ($user && $user->id) {
                info('Found user by id', ['user' => $user->id]);

                return $user;
            }
        }

        if ($email) {
            $user = gateway('northstar')->asClient()->getUser('email', $email);

            if ($user && $user->id) {
                info('Found user by email', ['user' => $user->id]);

                return $user;
            }
        }

        if (! $mobile) {
            return null;
        }

        $user = gateway('northstar')->asClient()->getUser('mobile', $mobile);

        if ($user && $user->id) {
            info('Found user by mobile', ['user' => $user->id]);

            return $user;
        }

        return null;
    }

    /**
     * Creates new user with record user data.
     *
     * @return NorthstarUser
     */
    private function createUser()
    {
        $user = gateway('northstar')->asClient()->createUser($this->userData);

        if (! $user->id) {
            throw new Exception(500, 'Unable to create user');
        }

        info('Created user', ['user' => $user->id]);

        return $user;
    }

    /**
     * Returns a post for given user and the record "Started registration", if it exists.
     *
     * @param NorthstarUser $user
     * @return array
     */
    public function getPost(NorthstarUser $user)
    {
        $result = app(Rogue::class)->getPosts([
            'action_id' => $this->postData['action_id'],
            'northstar_id' => $user->id,
            'type' => config('import.rock_the_vote.post.type'),
        ]);

        if (! $result['data']) {
            return null;
        }

        $key = 'Started registration';
        $recordPostDetails = $this->record->getPostDetails();
        $recordStartedRegistration = $recordPostDetails[$key];

        foreach ($result['data'] as $post) {
            if (! isset($post['details'])) {
                continue;
            }

            $details = json_decode($post['details']);

            if ($details->{$key} === $recordStartedRegistration) {
                info('Found post', ['post' => $post['id'], 'user' => $user->id]);

                return $post;
            }
        }

        return null;
    }

    /**
     * Creates new post with given Northstar user and record post data.
     *
     * @param NorthstarUser
     * @return array
     */
    private function createPost($user)
    {
        $post = app(Rogue::class)->createPost(array_merge([
            'northstar_id' => $user->id,
        ], $this->postData));

        info('Created post', ['post' => $post['data']['id'], 'user' => $user->id]);

        return $post;
    }

    /**
     * Determines if a current status should be changed to given value.
     *
     * @param string $currentStatus
     * @param string $newStatus
     * @return bool
     */
    public static function shouldUpdateStatus($currentStatus, $newStatus)
    {
        $statusHierarchy = config('import.rock_the_vote.status_hierarchy');

        $indexOfCurrentStatus = array_search($currentStatus, $statusHierarchy);
        $indexOfNewStatus = array_search($newStatus, $statusHierarchy);

        return $indexOfCurrentStatus < $indexOfNewStatus;
    }

    /**
     * Update Northstar user with record data.
     *
     * @return void
     */
    public function updateUserIfChanged(NorthstarUser $user)
    {
        $payload = [];

        if (self::shouldUpdateStatus($user->voter_registration_status, $this->userData['voter_registration_status'])) {
            $payload['voter_registration_status'] = $this->userData['voter_registration_status'];
        }

        if (config('import.rock_the_vote.update_user_sms_enabled') == 'true') {
            $payload = array_merge($payload, $this->getUserSmsSubscriptionUpdatePayload($user));
        }

        if (! count($payload)) {
            info('No changes to update for user', ['user' => $user->id]);

            return;
        }

        gateway('northstar')->asClient()->updateUser($user->id, $payload);

        info('Updated user', ['user' => $user->id]);
    }

    /**
     * Get fields and values to update given user with if their SMS subscription has changed.
     *
     * @return array
     */
    public function getUserSmsSubscriptionUpdatePayload(NorthstarUser $user)
    {
        // If registration does not have a mobile provided, there is nothing to update.
        if (! $this->userData['mobile']) {
            return [];
        }

        // We don't need to update user's SMS subscription if we already did for this registration.
        if (RockTheVoteLog::hasAlreadyUpdatedSmsSubscription($this->record, $user)) {
            return [];
        }

        $payload = $this->parseMobileChangeForUser($user);
        $payload = array_merge($payload, $this->parseSmsSubscriptionTopicsChangeForUser($user));
        $payload = array_merge($payload, $this->parseSmsStatusChangeForUser($user));

        return $payload;
    }

    /**
     * Returns payload to update mobile if user does not currently have one saved.
     *
     * @return array
     */
    public function parseMobileChangeForUser(NorthstarUser $user)
    {
        $fieldName = 'mobile';

        if ($user->{$fieldName}) {
            return [];
        }

        return [$fieldName => $this->userData[$fieldName]];
    }

    /**
     * Returns payload to update SMS subscription topics if they have changed.
     *
     * @return array
     */
    public function parseSmsSubscriptionTopicsChangeForUser(NorthstarUser $user)
    {
        $fieldName = 'sms_subscription_topics';
        $currentSmsTopics = ! empty($user->{$fieldName}) ? $user->{$fieldName} : [];

        // If user opted in to SMS, add the import topics to current topics.
        if ($this->smsOptIn) {
            return [$fieldName => array_unique(array_merge($currentSmsTopics, $this->userData[$fieldName]))];
        }

        // Nothing to remove if current topics in empty.
        if (! count($currentSmsTopics)) {
            return [];
        }

        // If user hasn't opted-in and has current topics, remove all import topics from current.
        $updatedSmsTopics = [];

        foreach ($currentSmsTopics as $topic) {
            if (! in_array($topic, explode(',', config('import.rock_the_vote.user.sms_subscription_topics')))) {
                array_push($updatedSmsTopics, $topic);
            }
        }

        return [$fieldName => $updatedSmsTopics];
    }

    /**
     * Returns payload to update SMS status if it has changed.
     *
     * @return array
     */
    public function parseSmsStatusChangeForUser(NorthstarUser $user)
    {
        $fieldName = 'sms_status';
        $currentSmsStatus = $user->{$fieldName};
        $importSmsStatus = $this->userData[$fieldName];

        /**
         * If current status is null or undeliverable, update status per whether they opted in
         * via the RTV form.
         *
         * This is the only scenario when we want to change an existing user's status to stop.
         */
        if ($currentSmsStatus == SmsStatus::$undeliverable || ! $currentSmsStatus) {
            return [$fieldName => $importSmsStatus];
        }

        if ($this->smsOptIn && in_array($currentSmsStatus, [
            SmsStatus::$less,
            SmsStatus::$pending,
            SmsStatus::$stop,
        ])) {
            return [$fieldName => $importSmsStatus];
        }

        return [];
    }

    /**
     * Updates Rogue post with record data if it should be updated.
     *
     * @param array $post
     * @return void
     */
    private function updatePostIfChanged($post)
    {
        if (! self::shouldUpdateStatus($post['status'], $this->postData['status'])) {
            info('No changes to update for post', ['post' => $post['id']]);

            return;
        }

        app(Rogue::class)->updatePost($post['id'], ['status' => $this->postData['status']]);

        info('Updated post', ['post' => $post['id'], 'status' => $this->postData['status']]);
    }

    /**
     * Send Northstar user a password reset email.
     *
     * @param NorthstarUser $user
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

    /**
     * Returns the record passed to this job.
     *
     * @return array
     */
    public function getParameters()
    {
        return get_object_vars($this->record);
    }
}
