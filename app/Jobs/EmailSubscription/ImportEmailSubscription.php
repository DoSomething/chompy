<?php

namespace Chompy\Jobs;

use Exception;
use Chompy\ImportType;
use Chompy\Models\ImportFile;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportEmailSubscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The email to subscribe.
     *
     * @var string
     */
    protected $email;
    /**
     * The first name of the user to subscribe.
     *
     * @var string
     */
    protected $first_name;
    /**
     * The source detail if new user.
     *
     * @var string
     */
    protected $source_detail;
    /**
     * The email subscription topic to add.
     *
     * @var array
     */
    protected $email_subscription_topic;

    /**
     * Create a new job instance.
     *
     * @param array $record
     * @param ImportFile $importFile
     * @param array $importOptions
     * @return void
     */
    public function __construct($record, ImportFile $importFile, $importOptions)
    {
        $this->email = $record['email'];
        $this->first_name = isset($record['first_name']) ? $record['first_name'] : null;
        $this->source_detail = $importOptions['source_detail'];
        $this->email_subscription_topic = $importOptions['email_subscription_topic'];
        $this->importFile = $importFile;
    }

    /**
     * Execute the job to create or update users by email and set subscription topics.
     *
     * @return array
     */
    public function handle()
    {
        info('progress_log: Processing: ' . $this->email);

        if ($user = $this->getUser()) {
            $this->updateUser($user);
        } else {
            $user = $this->createUser();
            $this->sendUserPasswordReset($user);
        }

        $this->importFile->incrementImportCount();
    }

    /**
     * Check if user exists by email.
     *
     * @return NorthstarUser
     */
    private function getUser()
    {
        $user = gateway('northstar')->asClient()->getUser('email', $this->email);
        if ($user && $user->id) {
            return $user;
        }
    }

    /**
     * Creates new user by email.
     *
     * @param array $record
     * @return NorthstarUser
     */
    private function createUser()
    {
        $user = gateway('northstar')->asClient()->createUser([
            'email' => $this->email,
            'first_name' => $this->first_name,
            // We need to pass a source in order to save through the source_detail.
            'source' => config('services.northstar.client_credentials.client_id'),
            'source_detail' => $this->source_detail,
            'email_subscription_status' => true,
            'email_subscription_topics' => [$this->email_subscription_topic],
        ]);

        if (! $user->id) {
            throw new Exception(500, 'Unable to create user: ' . $this->email);
        }
        info('Subscribed new user', ['user' => $user->id]);

        return $user;
    }

    /**
     * Update Northstar user's subscription topics.
     *
     * @param object $user
     */
    private function updateUser($user)
    {
        $existingTopics = ! empty($user->email_subscription_topics) ? $user->email_subscription_topics : [];
        $newTopics = array_unique(array_merge($existingTopics, [$this->email_subscription_topic]));

        // Update the user, filtering out null values so we don't unset an existing 'first_name'.
        gateway('northstar')->asClient()->updateUser($user->id, array_filter([
            'first_name' => $this->first_name,
            'email_subscription_status' => true,
            'email_subscription_topics'  => $newTopics,
        ]));

        info('Subscribed existing user', ['user' => $user->id]);
    }

    /**
     * Send Northstar user a password reset email.
     *
     * @param object $user
     */
    private function sendUserPasswordReset($user)
    {
        $logParams = ['user' => $user->id];
        $selectedTopic = $this->email_subscription_topic;

        $config = ImportType::getConfig(ImportType::$emailSubscription);
        $selectedTopicResetConfig = $config['topics'][$selectedTopic]['reset'];
        $resetType = $selectedTopicResetConfig['type'];
        $logParams['type'] = $resetType;

        if ($selectedTopicResetConfig['enabled'] !== 'true') {
            info('Reset email is disabled. Would have sent reset email', $logParams);

            return;
        }

        gateway('northstar')->asClient()->sendUserPasswordReset($user->id, $resetType);
        info('Sent reset email', $logParams);
    }
}
