<?php

namespace Chompy\Http\Controllers\Web;

use Illuminate\Http\Request;
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
        $rows = RockTheVoteLog::where('user_id', $id)->paginate(15);

        return view('pages.users.show', ['user_id' => $id, 'rows' => $rows]);
    }
}
