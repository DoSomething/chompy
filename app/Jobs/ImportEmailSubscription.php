<?php

namespace Chompy\Jobs;

use Exception;
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
     * @var array
     */
    protected $email;
    /**
     * The first name of the user to subscribe.
     *
     * @var array
     */
    protected $first_name;
    /**
     * The source detail if new user.
     *
     * @var string
     */
    protected $source_detail;
    /**
     * The email subscription topics to add.
     *
     * @var array
     */
    protected $email_subscription_topics;

    /**
     * Create a new job instance.
     *
     * @param string $email
     * @param string $sourceDetail
     * @param array $emailSubscriptionTopics
     * @return void
     */
    public function __construct($email, $firstName, $sourceDetail, $emailSubscriptionTopics)
    {
        $this->email = $email;
        $this->first_name = $firstName;
        $this->source_detail = $sourceDetail;
        $this->email_subscription_topics = $emailSubscriptionTopics;
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
            $this->createUser();
        }
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
            'email_subscription_topics' => $this->email_subscription_topics,
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
        $newTopics = array_unique(array_merge($existingTopics, $this->email_subscription_topics));

        gateway('northstar')->asClient()->updateUser($user->id, [
            'first_name' => $this->first_name,
            'email_subscription_status' => true,
            'email_subscription_topics'  => $newTopics,
        ]);
        info('Subscribed existing user', ['user' => $user->id]);
    }
}
