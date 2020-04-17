<?php

namespace Chompy\Http\Controllers\Web;

use Carbon\Carbon;
use Chompy\ImportType;
use League\Csv\Reader;
use Illuminate\Http\Request;
use Chompy\Models\ImportFile;
use Chompy\Models\RockTheVoteLog;
use Chompy\Jobs\ImportFileRecords;
use Illuminate\Support\Facades\Input;
use Chompy\Http\Controllers\Controller;

class TestController extends Controller
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

    /*
     * Show the create test request form.
     */
    public function create($importType)
    {
        $data = [];

        if ($importType === ImportType::$rockTheVote) {
            $data = [
                'email' => 'chompy-tester@dosomething.org',
                'referral' => 'user:'.\Auth::user()->northstar_id.',source:test,source_detail:ChompyUI,referral=true',
                'started-registration' => \Carbon\Carbon::now()->format('Y-m-d H:i:s O'),
            ];
        }

        return view('pages.tests.create', [
            'importType' => $importType,
            'config' => ImportType::getConfig($importType),
            'data' => $data,
        ]);
    }

    /**
     * Submit the test request.
     *
     * @param Request $request
     * @param string $importType
     */
    public function submit(Request $request, $importType)
    {
        /*
        ImportFileRecords::dispatch(\Auth::user(), $path, $importType, $importOptions)->delay(now()->addSeconds(3));
        */

        return redirect('tests/'.$importType)
            ->withInput(Input::all())
            ->with('status', print_r($request->post(), true));
    }
}
