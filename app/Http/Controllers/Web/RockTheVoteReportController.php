<?php

namespace Chompy\Http\Controllers\Web;

use Illuminate\Http\Request;
use Chompy\Models\RockTheVoteReport;
use Chompy\Http\Controllers\Controller;

class RockTheVoteReportController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    /**
     * Create a Rock The Vote report.
     *
     * @return Response
     */
    public function create()
    {
        return view('pages.rock-the-vote-reports.create');
    }

    /**
     * Execute API request to create a Rock The Vote Report, and save ID to storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'since' => ['required'],
            'before' => ['required'],
        ]);

        // Execute API request to create a new Rock The Vote Report.
        $apiResponse = app('Chompy\Services\RockTheVote')->createReport($request->all());

        // Log our created report in the database, to keep track of reports requested.
        $report = RockTheVoteReport::createFromApiResponse($apiResponse, $request['since'], $request['before']);

        return redirect('rock-the-vote/reports/' . $report->id);
    }

    /**
     * Display Rock The Vote Report status information.
     *
     * @return Response
     */
    public function show($id)
    {
        return view('pages.rock-the-vote-reports.show', [
            'id' => $id,
            'report' => app('Chompy\Services\RockTheVote')->getReportStatusById($id),
        ]);
    }
}