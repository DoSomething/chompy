<?php

namespace Chompy\Http\Controllers\ThirdParty;

use Illuminate\Http\Request;
use Chompy\Http\Controllers\Controller;
use Chompy\Jobs\CreateCallPowerPostInRogue;

class CallPowerController extends Controller
{
    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
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

        // Send to queued job.
        CreateCallPowerPostInRogue::dispatch($request);
    }
}
