<?php

namespace Chompy\Http\Controllers\Web;

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
