<?php

namespace Chompy\Http\Controllers\ThirdParty;

use Exception;
use Illuminate\Http\Request;
use Chompy\Services\Rogue;
use Chompy\Http\Controllers\Controller;

class CallPowerController extends Controller
{

    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(Rogue $rogue)
    {
    	$this->rogue = $rogue;

    }

    /**
     *
     * @param Request $request
     */
    public function store(Request $request)
    {
        $request->validate([
            'mobile' => 'required',
            'callpower_campaign_id' => 'required|integer',
            'status' => 'required|string',
            'call_timestamp' => 'required|date',
            'call_duration' => 'required|integer',
            'campaign_target_name' => 'required|string',
            'campaign_target_title' => 'required|string',
            'campaign_target_district' => 'required|string',
            'callpower_campaign_name' => 'required|string',
            'number_dialed_into' => 'required',
        ]);

        // Using the mobile number, get or create a northstar_id.
        $user = $this->getOrCreateUser($request['mobile']);

        // Using the callpower_campaign_id, get the action_id from Rogue.
        $action = $this->rogue->getActionFromCallPowerCampaignId($request['callpower_campaign_id']);

        // Check if the post exists in Rogue. If not, create the post.
        $existingPost = $this->rogue->getPost([
            'action_id' => $action['data'][0]['id'],
            'northstar_id' => $user->id,
        ]);

        if (! $existingPost['data']) {
            info('creating post in rogue for northstar user: ' . $user->id);

            $details = $this->extractDetails($request);

        	// Determine source details.
            $post = $this->rogue->createPost([
              'northstar_id' => $user->id,
              'action_id' => $action['data'][0]['id'],
              'type' => 'phone-call',
              'status' => $request['status'] === 'completed' ? 'accepted' : 'incomplete',
              'quantity' => 1,
              'source' => 'CallPower',
              'details' => $details,
          ]);

            if ($post['data']) {
                info('post created in rogue for northstar user: ' . $user->id);
            }
        }
    }

    /**
     * For a given mobile number, first check if we have a northstar ID, then grab the user using that.
     * Otherwise, create the user.
     *
     * @param integer $mobile
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
      }

    	// Log if the user was successfully created.
      if ($user->id) {
        info('created user', ['user' => $user->id]);
    } else {
        throw new Exception(500, 'Unable to create user with mobile: ' . $mobile);
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
