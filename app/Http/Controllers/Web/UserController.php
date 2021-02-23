<?php

namespace Chompy\Http\Controllers\Web;

use Chompy\Models\MutePromotionsLog;
use Chompy\Models\RockTheVoteLog;
use Chompy\Http\Controllers\Controller;

class UserController extends Controller
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
     * Displays logs for a user.
     *
     * @return Response
     */
    public function show($id)
    {
        return view('pages.users.show', [
            'id' => $id,
            'mutePromotionsLogs' => MutePromotionsLog::where('user_id', $id)->get(),
            'rockTheVoteLogs' => RockTheVoteLog::where('user_id', $id)->paginate(15),
        ]);
    }
}
