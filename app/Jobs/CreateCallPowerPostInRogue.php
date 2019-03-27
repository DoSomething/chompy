<?php

namespace Chompy\Jobs;

use Exception;
use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateCallPowerPostInRogue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The call parameters sent from CallPower.
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
     * Execute the job to create a CallPower post in Rogue.
     *
     * @return array
     */
    public function handle(Rogue $rogue)
    {
        // Using the mobile number, get or create a northstar_id.
        $user = $this->getOrCreateUser($this->parameters['mobile']);

        // Using the callpower_campaign_id, get the action id from Rogue.
        $actionId = $rogue->getActionIdFromCallPowerCampaignId($this->parameters['callpower_campaign_id']);

        $details = $this->extractDetails($this->parameters);

        info('creating post in rogue for northstar user: ' . $user->id . ' and details: ' . $details);

        // Determine source details.
        $post = $rogue->createPost([
            'northstar_id' => $user->id,
            'action_id' => $actionId,
            'type' => 'phone-call',
            'status' => $this->parameters['status'] === 'completed' ? 'accepted' : 'incomplete',
            'quantity' => 1,
            'source_details' => 'CallPower',
            'details' => $details,
            'dont_send_to_blink' => true,
        ]);

        if ($post['data']) {
            info('post created in rogue for northstar user: ' . $user->id);
        }
    }

    /**
     * For a given mobile number, first check if we have a northstar ID, then grab the user using that.
     * Otherwise, create the user.
     *
     * @param int $mobile
     * @return NorthstarUser
     */
    private function getOrCreateUser($mobile)
    {
        // Get the user by mobile number.
        info('getting user with the mobile: ' . $mobile);
        $user = gateway('northstar')->asClient()->getUser('mobile', $mobile);

        // If there is no user, create one.
        if (is_null($user)) {
            $user = gateway('northstar')->asClient()->createUser([
                'mobile' => $mobile,
            ]);

            // Log if the user was successfully created.
            if ($user->id) {
                info('created user', ['user' => $user->id]);
            } else {
                throw new Exception('Unable to create user with mobile: ' . $mobile);
            }
        }

        return $user;
    }

    /**
     * Parse the call and return details we want to store in Rogue as a JSON object.
     *
     * @param array $call
     */
    private function extractDetails($call)
    {
        return json_encode([
            'status_details' => $call['status'],
            'call_timestamp' => $call['call_timestamp'],
            'call_duration' => $call['call_duration'],
            'campaign_target_name' => $call['campaign_target_name'],
            'campaign_target_title' => $call['campaign_target_title'],
            'campaign_target_district' => $call['campaign_target_district'],
            'callpower_campaign_name' => $call['callpower_campaign_name'],
            'number_dialed_into' => $call['number_dialed_into'],
        ]);
    }
}
