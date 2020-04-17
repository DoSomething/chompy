<?php

namespace Chompy\Http\Controllers\Web;

use Carbon\Carbon;
use Chompy\ImportType;
use League\Csv\Reader;
use Illuminate\Http\Request;
use Chompy\Models\ImportFile;
use Chompy\Models\RockTheVoteLog;
use Chompy\Jobs\ImportFileRecords;
use Chompy\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

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
        return view('pages.tests.create', [
            'importType' => $importType,
            'config' => ImportType::getConfig($importType),
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
            ->with('status', print_r($request->post(), true));
    }
}
