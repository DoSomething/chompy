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
        $this->import_file_id = $importFileId;
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
            $newStatus = $this->getVoterRegistrationStatusChange($user->voter_registration_status, $this->userData['voter_registration_status']);

            if ($newStatus) {
                $this->updateUser($user, ['voter_registration_status' => $newStatus]);
            }
        } else {
            $user = $this->createUser($this->userData);

            info('Created user', ['user' => $user->id]);

            $this->sendUserPasswordResetIfSubscribed($user);
        }

        RockTheVoteLog::createFromRecord($this->record, $user, $this->import_file_id);

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

        $newStatus = $this->getVoterRegistrationStatusChange($post['status'], $this->postData['status']);

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
     * Creates new user with given data.
     *
     * @param array $data
     * @return NorthstarUser
     */
    private function createUser($data)
    {
        $user = gateway('northstar')->asClient()->createUser($data);

        if (! $user->id) {
            throw new Exception(500, 'Unable to create user');
        }

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
