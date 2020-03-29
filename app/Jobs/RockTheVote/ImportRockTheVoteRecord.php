<?php

namespace Chompy\Jobs;

use Exception;
use Chompy\ImportType;
use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Chompy\RockTheVoteRecord;
use Chompy\Models\RockTheVoteLog;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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
    public function __construct($record, $importFileId)
    {
        $this->config = ImportType::getConfig(ImportType::$rockTheVote);
        $this->record = new RockTheVoteRecord($record, $this->config);
        $this->userData = $this->record->userData;
        $this->postData = $this->record->postData;
        $this->importFileId = $importFileId;
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

        if ($user && $user->id) {
            $this->updateUserIfChanged($user);
        } else {
            $user = $this->createUser();

            info('Created user', ['user' => $user->id]);

            $this->sendUserPasswordResetIfSubscribed($user);
        }

        RockTheVoteLog::createFromRecord($this->record, $user, $this->importFileId);

        $existingPosts = $rogue->getPost([
            'action_id' => $this->postData['action_id'],
            'northstar_id' => $user->id,
        ]);

        if (! $existingPosts['data']) {
            $post = $rogue->createPost(array_merge(['northstar_id' => $user->id], $this->postData));

            info('Created post', ['post' => $post['data']['id'], 'user' => $user->id]);

            return;
        }

        $post = $existingPosts['data'][0];

        info('Found post', ['post' => $post['id'], 'user' => $user->id]);

        $this->updatePostIfChanged($post);
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
     * @param array $data
     * @return NorthstarUser
     */
    private function createUser()
    {
        $user = gateway('northstar')->asClient()->createUser($this->userData);

        if (! $user->id) {
            throw new Exception(500, 'Unable to create user');
        }

        return $user;
    }

    /**
     * Determines if a current status should be changed to given value.
     *
     * @param string $currentStatus
     * @param string $newStatus
     * @return bool
     */
    private function shouldUpdateStatus($currentStatus, $newStatus)
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

        return $indexOfCurrentStatus < $indexOfNewStatus;
    }

    /**
     * Update Northstar user with record data.
     *
     * @param object $user
     */
    public function updateUserIfChanged($user)
    {
        $payload = [];

        if ($this->shouldUpdateStatus($user->voter_registration_status, $this->userData['voter_registration_status'])) {
            $payload['voter_registration_status'] = $this->userData['voter_registration_status'];
        }

        // @TODO: Check for SMS status change.

        if (! count($payload)) {
            info('No changes to update for user', ['user' => $user->id]);

            return;
        }

        gateway('northstar')->asClient()->updateUser($user->id, $payload);

        info('Updated user', ['user' => $user->id, 'voter_registration_status' => $this->userData['voter_registration_status']]);
    }

    /**
     * Update post with record data.
     *
     * @param array $post
     */
    private function updatePostIfChanged($post)
    {
        if (! $this->shouldUpdateStatus($post['status'], $this->postData['status'])) {
            info('No changes to update for post', ['post' => $post['id']]);

            return;
        }

        $rogue->updatePost($post['id'], ['status' => $this->postData['status']]);

        info('Updated post', ['post' => $post['id'], 'status' => $this->postData['status']]);
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

    /**
     * Returns the record passed to this job.
     *
     * @return array
     */
    public function getParameters()
    {
        return [
            'userData' => $this->userData,
            'postData' => $this->postData,
        ];
    }
}
