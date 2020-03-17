<?php

namespace Chompy\Http\Controllers\Web;

use Chompy\ImportType;
use Illuminate\Http\Request;
use Chompy\Models\RockTheVoteReport;
use Chompy\Http\Controllers\Controller;
use Chompy\Jobs\ImportRockTheVoteReport;

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
        return view('pages.rock-the-vote-reports.create', [
            'config' => ImportType::getConfig(ImportType::$rockTheVote),
        ]);
    }

    /**
     * Execute API request to create a Rock The Vote Report, and save ID to storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'since' => 'required|date_format:Y-m-d H:i:s',
            'before' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $report = RockTheVoteReport::createViaApi($request['since'], $request['before']);

        ImportRockTheVoteReport::dispatch(\Auth::user(), $report);

        return redirect('rock-the-vote-reports/' . $report->id);
    }

    /**
     * Display Rock The Vote Report status information.
     *
     * @return Response
     */
    public function show($id)
    {
        return view('pages.rock-the-vote-reports.show', [
            'report' => RockTheVoteReport::find($id),
        ]);
    }

    /**
     * Display a listing of Rock The Vote reports.
     *
     * @return Response
     */
    public function index()
    {
        return view('pages.rock-the-vote-reports.index', [
            'data' => RockTheVoteReport::orderBy('id', 'desc')->paginate(15),
        ]);
    }
}
