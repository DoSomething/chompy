<?php

namespace Chompy\Jobs;

use Exception;
use Chompy\ImportType;
use Illuminate\Bus\Queueable;
use Chompy\Traits\ImportFromFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportEmailSubscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ImportFromFile;

    /**
     * The email to subscribe
     *
     * @var array
     */
    protected $email;
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
     * @return void
     */
    public function __construct($email)
    {
        $config = ImportType::getConfig(ImportType::$emailSubscription);
        $this->email = $email;
        $this->source_detail = $config['user']['source_detail'];
        $this->email_subscription_topics = [$config['user']['email_subscription_topics']];
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
     * Check if user exists for emaill.
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
     * Creates new user from record.
     *
     * @param array $record
     * @return NorthstarUser
     */
    private function createUser()
    {
        $user = gateway('northstar')->asClient()->createUser([
            'email' => $this->email,
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
        gateway('northstar')->asClient()->updateUser($user->id, [
            'email_subscription_status' => true,
            // TODO: Merge arrays, this is overwriting.
            'email_subscription_topics'  => $this->email_subscription_topics,
        ]);
        info('Subscribed existing user', ['user' => $user->id]);
    }
}
