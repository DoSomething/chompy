<?php

namespace Chompy\Jobs;

use Exception;
use Chompy\SmsStatus;
use Chompy\ImportType;
use Chompy\Services\Rogue;
use Illuminate\Support\Arr;
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
     * @var RockTheVoteRecord
     */
    protected $record;

    /**
     * The mobile user (if different from "primary" user).
     *
     * @var NorthstarUser
     */
    protected $mobileUser;

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
     * Execute the job to upsert a user and their voter registration post.
     *
     * @return array
     */
    public function handle()
    {
        $postDetails = $this->record->getPostDetails();

        info('Processing Rock The Vote record', ['started_registration' => $postDetails['Started registration']]);

        $user = $this->getUser();

        if (! $user) {
            info('User not found, creating user');

            $user = $this->createUser();

            $post = $this->createPost($user);

            RockTheVoteLog::createFromRecord($this->record, $user, $this->importFile);

            $this->sendUserPasswordResetIfSubscribed($user);

            return $this->formatResponse($user, $post);
        }

        if (RockTheVoteLog::getByRecord($this->record, $user)) {
            $details = $this->record->getPostDetails();
            $skipLogMessage = 'Skipping record that has already been imported';
            $skipLogInfo = [
                'user' => $user->id,
                'status' => $details['Status'],
                'started_registration' => $details['Started registration'],
            ];

            info($skipLogMessage, $skipLogInfo);

            $this->importFile->incrementSkipCount();

            return [
                'response' => array_merge(['message' => $skipLogMessage], ['keys' => $skipLogInfo]),
            ];
        }

        $user = $this->updateUserVoterRegistrationStatusIfChanged($user);

        // If registration does not have a mobile provided, no need to update SMS subscription.
        if (isset($this->userData['mobile'])) {
            $user = $this->updateUserSmsSubscriptionIfChanged($user);
        }

        if ($post = $this->getPost($user)) {
            $post = $this->updatePostIfChanged($post);
        } else {
            $post = $this->createPost($user);
        }

        RockTheVoteLog::createFromRecord($this->record, $user, $this->importFile);

        return $this->formatResponse($user, $post);
    }

    /**
     * When running this job synchronously (e.g. ImportFileController), we return an
     * array of context/results from the 'handle' method to aid with testing.
     *
     * @return array
     */
    private function formatResponse(NorthstarUser $user, array $post)
    {
        $userFields = [
            'id', 'email_preview', 'mobile_preview', 'voter_registration_status', 'referrer_user_id',
            'sms_status', 'sms_subscription_topics', 'email_subscription_status', 'email_subscription_topics',
        ];

        return [
            'record.userData' => $this->record->userData,
            'record.postData' => $this->record->postData,
            'user' => Arr::only($user->toArray(), $userFields),
            'mobile_user' => ! empty($this->mobileUser) ? Arr::only($this->mobileUser->toArray(), $userFields) : null,
            'post' => Arr::only($post, [
                'id',
                'type',
                'action_id',
                'status',
                'details',
                'referrer_user_id',
                'group_id',
            ]),
        ];
    }

    /**
     * Check for user first by id, next by email, last by mobile.
     *
     * @param string $id
     * @param string $email
     * @param string $mobile
     * @return NorthstarUser
     */
    private function getUser()
    {
        if ($this->userData['id']) {
            $user = gateway('northstar')->asClient()->getUser($this->userData['id']);

            if ($user && $user->id) {
                info('Found user by id', ['user' => $user->id]);

                return $user;
            }
        }

        if ($this->userData['email']) {
            $user = gateway('northstar')->asClient()->getUserByEmail($this->userData['email']);

            if ($user && $user->id) {
                info('Found user by email', ['user' => $user->id]);

                return $user;
            }
        }

        if (! isset($this->userData['mobile'])) {
            return null;
        }

        $user = gateway('northstar')->asClient()->getUserByMobile($this->userData['mobile']);

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
     * Updates given user with given data.
     *
     * @param NorthstarUser $user
     * @param array $data
     * @return NorthstarUser
     */
    private function updateUser($user, $data)
    {
        if (! $data) {
            info('No updates to make', ['user' => $user->id]);

            return $user;
        }

        $user = gateway('northstar')->asClient()->updateUser($user->id, $data);

        info('Updated user', ['user' => $user->id, 'changed' => array_keys($data)]);

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

        return $post['data'];
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
     * Updates user's voter registration status if changed per import.
     *
     * @return NorthstarUser
     */
    public function updateUserVoterRegistrationStatusIfChanged(NorthstarUser $user)
    {
        info('Checking for voter registration status update', ['user' => $user->id]);

        if (! self::shouldUpdateStatus($user->voter_registration_status, $this->userData['voter_registration_status'])) {
            info('No updates to make for voter registration status', ['user' => $user->id]);

            return $user;
        }

        return $this->updateUser($user, [
            'voter_registration_status' =>  $this->userData['voter_registration_status'],
        ]);
    }

    /**
     * Updates user's SMS subscription if changed per import.
     *
     * @return NorthstarUser
     */
    public function updateUserSmsSubscriptionIfChanged(NorthstarUser $user)
    {
        info('Checking for SMS subscription update', ['user' => $user->id]);

        // Don't update import user's SMS subscription if we already did for this registration.
        if (RockTheVoteLog::hasAlreadyUpdatedSmsSubscription($this->record, $user)) {
            info('Already updated SMS subscription for this registration', ['user' => $user->id]);

            return $user;
        }

        // If import user already has a mobile, don't change it - just update subscription.
        if ($user->mobile) {
            info('User has an existing mobile', ['user' => $user->id]);

            return $this->updateUser($user, $this->getUserSmsSubscriptionUpdatePayload($user));
        }

        // Check if another user already owns the import mobile.
        $this->mobileUser = gateway('northstar')->asClient()->getUserByMobile($this->userData['mobile']);

        // If another user owns the import mobile, update their subscription.
        if ($this->mobileUser) {
            info('Mobile is already taken, updating mobile user', ['user' => $user->id, 'mobile_user' => $this->mobileUser->id]);

            $this->mobileUser = $this->updateUser($this->mobileUser, $this->getUserSmsSubscriptionUpdatePayload($this->mobileUser));

            return $user;
        }

        // Otherwise, update the import user's mobile and subscription.
        info('Did not find existing user with mobile', ['user', $user->id]);

        return $this->updateUser($user, array_merge(
            ['mobile' => $this->userData['mobile']],
            $this->getUserSmsSubscriptionUpdatePayload($user),
        ));
    }

    /**
     * Get fields and values to update given user with if their SMS subscription has changed.
     *
     * @return array
     */
    public function getUserSmsSubscriptionUpdatePayload(NorthstarUser $user)
    {
        return array_merge(
            $this->parseSmsStatusChangeForUser($user),
            $this->parseSmsSubscriptionTopicsChangeForUser($user),
        );
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
        $updatedSmsTopics = [];

        // If user opted in to SMS, add the import topics to current topics.
        if ($this->smsOptIn) {
            $updatedSmsTopics = array_unique(array_merge($currentSmsTopics, $this->userData[$fieldName]));

            // If we didn't add any new topics, nothing to update.
            if (count($updatedSmsTopics) === count($currentSmsTopics)) {
                return [];
            }

            return [$fieldName => $updatedSmsTopics];
        }

        // Nothing to remove if current topics in empty.
        if (! count($currentSmsTopics)) {
            return [];
        }

        // If user hasn't opted-in and has current topics, remove all import topics from current.
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
     * @return array
     */
    private function updatePostIfChanged($post)
    {
        if (! self::shouldUpdateStatus($post['status'], $this->postData['status'])) {
            info('No changes to update for post', ['post' => $post['id']]);

            return $post;
        }

        $post = app(Rogue::class)->updatePost($post['id'], ['status' => $this->postData['status']]);

        info('Updated post', ['post' => $post['id'], 'status' => $post['status']]);

        return $post;
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
