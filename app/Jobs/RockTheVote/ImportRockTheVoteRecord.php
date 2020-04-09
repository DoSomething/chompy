<?php

namespace Chompy\Jobs;

use Exception;
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

        $updateUserPayload = $this->getUpdateUserPayload($user);

        if (count($updateUserPayload)) {
            gateway('northstar')->asClient()->updateUser($user->id, $updateUserPayload);
            info('Updated user', ['user' => $user->id]);
        } else {
            info('No changes to update for user', ['user' => $user->id]);
        }

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
     * @param NorthstarUser $user
     */
    private function getUpdateUserPayload($user)
    {
        $payload = [];

        if (self::shouldUpdateStatus($user->voter_registration_status, $this->userData['voter_registration_status'])) {
            $payload = array_only($this->userData, ['voter_registration_status']);
        }

        if (config('rock_the_vote.update_user_sms_enabled') === 'false') {
            return $payload;
        }

        return array_merge($payload, $this->getUpdateUserSmsSubscriptionPayload($user));
    }

    /**
     * Get fields and values to update if user SMS preferences have changed.
     *
     * @return array
     */
    public function getUpdateUserSmsSubscriptionPayload($user)
    {
        $result = [];

        // If registration does not have a mobile provided, there is nothing to update.
        if (! $this->userData['mobile']) {
            return $result;
        }

        /**
         * If we have a log for this registration that contains phone, we've already
         * updated user's subscription for this registration and should not proceed.
         */
        if (RockTheVoteLog::hasAlreadyUpdatedSmsSubscription($this->record, $user)) {
            return $result;
        }

        // @TODO: Check for changes to user's SMS status and subscription topics.

        // Save mobile if we don't have it currently stored on the user.
        if (! $user->mobile) {
            $result['mobile'] = $this->userData['mobile'];
        }

        return $result;
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
