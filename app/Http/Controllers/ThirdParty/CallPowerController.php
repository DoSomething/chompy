<?php

namespace Rogue\Http\Controllers\ThirdParty;

use Illuminate\Http\Request;
use Chompy\Services\Rogue;

class CallPowerController extends Controller
{

    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct()
    {
      // @TODO: add role middleware
    }

    /**
     *
     * @param Request $request
     */
    public function store(Request $request)
    {
    	$request->validate([
    		// @TODO: how do we validate a phone number? What format will this come in from CallPower?
            'mobile' => 'required',
            'callpower_campaign_id' => 'required|integer',
            // QUESTION: do we want to make it this rigid? What if CallPower statuses change?
            'status' => 'required|string|in:completed,busy,failed,no answer,cancelled, unknown',
            'details' => 'required',
        ]);

        // Using the mobile number, get or create a northstar_id.
        $user = $this->getOrCreateUser($request['mobile']);

        // Using the callpower_campaign_id, get the action_id from Rogue.
        $action = $rogue->getActionFromCallPowerCampaignId($request['callpower_campaign_id']);

        // Check if the post exists in Rogue. If not, create the post.
       	$existingPost = $rogue->getPost([
            'action_id' => $action['data']['id'],
            'northstar_id' => $user->id,
        ]);

        if (!$existingPost['data']) {
        	info('creating post in rogue for northstar user: ' . $user->id);

        	$details = $this->extractDetails($request);

        	// Determine source details.
        	$post = $rogue->createPost([
        		'northstar_id' => $user->id,
        		'action_id' => $$action['data']['id'],
        		'type' => 'phone-call',
        		'status' => $request['status'] === 'completed' ? 'accepted' : 'incomplete',
        		'quantity' => 1,
        		'source' => 'CallPower',
        		'source_details' => $details,
        	]);
        }
    }

    /**
     * For a given mobile number, first check if we have a northstar ID, then grab the user using that.
     * Otherwise, create the user.
     *
     * @param integer $mobile
     * @return NorthstarUser
     */
    pritvate function getOrCreateUser($mobile)
    {
    	// Get the user by mobile number.
    	info('getting user with the mobile: ' . $mobile);
    	$user = gateway('northstar')->asClient()->getUser('mobile', $mobile);

    	// If there is no user, create one.
    	if (is_null($user)) {
    		$user = gateway('northstar')->asClient()->createUser([
                'mobile' => $mobile,
                'source' => env('NORTHSTAR_CLIENT_ID'),
            ]);
    	}

    	// Log if the user was successfully created.
            if ($user->id) {
                info('created user', ['user' => $user->id]);
            } else {
                throw new Exception(500, 'Unable to create user with mobile: ' . $mobile);
            }
    }

    /**
     * Parse the call and return details we want to store in Rogue as a JSON object.
     *
     * @param array $call
     */
    private function extractDetails($call)
    {
    	// If the call isn't completed, store CallPower status status.
    }
}
