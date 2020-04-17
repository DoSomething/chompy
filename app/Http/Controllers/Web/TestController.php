<?php

namespace Chompy\Http\Controllers\Web;

use Carbon\Carbon;
use Chompy\ImportType;
use Chompy\RockTheVoteRecord;
use Illuminate\Http\Request;
use Chompy\Models\ImportFile;
use Illuminate\Support\Facades\Input;
use Chompy\Http\Controllers\Controller;
use Chompy\Jobs\ImportRockTheVoteRecord;

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
        $userId = \Auth::user()->northstar_id;
        $user = gateway('northstar')->asClient()->getUser('id', $userId);

        if ($importType === ImportType::$rockTheVote) {
            $data = [
                'addr_street1' => $user->addr_street1,
                'addr_street2' => $user->addr_street2,
                'addr_city' => $user->addr_city,
                'addr_zip' => $user->addr_zip,          
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'mobile' => $user->mobile,
                'referral' => 'user:'.$userId.',source:test,source_detail:ChompyUI',
                'started_registration' => \Carbon\Carbon::now()->format('Y-m-d H:i:s O'),
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
        if ($importType === ImportType::$rockTheVote) {
            $row = [
                'Email address' => $request->input('email'),
                'Finish with State' => $request->input('status'),
                'First name' => $request->input('first_name'),
                'Home address' => $request->input('addr_street1'),
                'Home city' => $request->input('addr_city'),
                'Home unit' => $request->input('addr_street2'),
                'Home zip code' => $request->input('addr_zip'),
                'Last name' => $request->input('last_name'),
                'Opt-in to Partner SMS/robocall' => $request->input('sms_opt_in') ?: 'No',
                'Opt-in to Partner email?' => $request->input('email_opt_in') ?: 'No',
                'Phone' => $request->input('mobile'),
                'Pre-Registered' => $request->input('pre_registered') ?: 'No',
                'Started registration' => $request->input('started_registration'),
                'Status' => $request->input('status'),
                'Tracking Source' => $request->input('referral'),
            ];

            $importFile = new ImportFile();

            $importFile->user_id = \Auth::user()->northstar_id;
            $importFile->row_count = 1;
            $importFile->filepath = 'n/a';
            $importFile->import_type = $importType;
            $importFile->save();

            $job = new ImportRockTheVoteRecord($row, $importFile);

            $job->handle();
        }

        return redirect('tests/'.$importType)
            ->withInput(Input::all())
            ->with('status', print_r($request->post(), true));
    }
}
