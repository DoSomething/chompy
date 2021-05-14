<?php

namespace Chompy\Http\Controllers\ThirdParty;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Chompy\Http\Controllers\Controller;
use Chompy\Jobs\ImportSoftEdgeRecord;

class SoftEdgeController extends Controller
{
    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('token:X-DS-SoftEdge-API-Key');
    }

    /**
     * @param Request $request
     */
    public function store(Request $request)
    {
        $parameters = $request->validate([
            'northstar_id' => 'required',
            'action_id' => 'required|integer',
            'email_timestamp' => 'required|date',
            'campaign_target_name' => 'required|string',
            'campaign_target_title' => 'nullable|string',
            'campaign_target_district' => 'nullable|string',
        ]);

        Log::debug('SoftEdgeController@store:' . json_encode($parameters));

        // Send to queued job.
        ImportSoftEdgeRecord::dispatch($parameters);

        return response()->json(['success' => true]);
    }
}
