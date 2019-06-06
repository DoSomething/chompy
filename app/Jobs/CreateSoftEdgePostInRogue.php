<?php

namespace Chompy\Jobs;

use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateSoftEdgePostInRogue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The call parameters sent from SoftEdge.
     *
     * @var array
     */
    protected $parameters;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Execute the job to create a SoftEdge post in Rogue.
     *
     * @return array
     */
    public function handle(Rogue $rogue)
    {
        $userId = $this->parameters['northstar_id'];

        $actionId = $this->parameters['action_id'];

        $details = $this->extractDetails($this->parameters);

        info('creating post in rogue for northstar user: ' . $userId . ' and details: ' . $details);

        // Determine source details.
        $post = $rogue->createPost([
            'northstar_id' => $userId,
            'action_id' => $actionId,
            'type' => 'email',
            'status' => 'accepted',
            'quantity' => 1,
            'source_details' => 'SoftEdge',
            'details' => $details,
        ]);

        if ($post['data']) {
            info('post created in rogue for northstar user: ' . $userId);
        }
    }

    /**
     * Parse the call and return details we want to store in Rogue as a JSON object.
     *
     * @param array $call
     */
    private function extractDetails($email)
    {
        return json_encode([
            'email_timestamp' => $email['email_timestamp'],
            'campaign_target_name' => $email['campaign_target_name'],
            'campaign_target_title' => $email['campaign_target_title'] ? $email['campaign_target_title'] : null,
            'campaign_target_district' => $email['campaign_target_district'] ? $email['campaign_target_district'] : null,
        ]);
    }
}
