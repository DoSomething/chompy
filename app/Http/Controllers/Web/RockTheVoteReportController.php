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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'id' => ['required', 'integer'],
            'since' => ['required'],
            'before' => ['required'],
        ]);

        // @TODO: Get Report ID by via RTV API request.
        $report = RockTheVoteReport::create($request->all());

        return redirect('rock-the-vote/reports/' . $report->id);
    }

    /**
     * Display a Rock The Vote report.
     *
     * @return Response
     */
    public function show($id)
    {
        $client = app('Chompy\Services\RockTheVote');

        return view('pages.rock-the-vote-reports.show', [
            'id' => $id,
            'report' => $client->getReportStatusById($id),
        ]);
    }
}
