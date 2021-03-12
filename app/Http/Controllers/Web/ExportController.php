<?php

namespace Chompy\Http\Controllers\Web;

use League\Csv\Writer;
use Chompy\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExportController extends Controller
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
     * Display a form to create an export.
     *
     * @param int $id
     * @return Response
     */
    public function create()
    {
        return view('pages.exports.create');
    }

    /**
     * Will create a CSV to download.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(Request $request)
    {
        $exportType = $request['type'];

        $data = DB::table('failed_jobs')->where('payload', 'LIKE', '%'.$exportType.'%')->get();

        $csv = Writer::createFromFileObject(new \SplTempFileObject());

        /**
         * For now, we only support downloading Mute Promotions failed jobs.
         * Hardcoding the headers as well as the values.
         */
        $csv->insertOne(['user_id']);

        foreach ($data as $failedJob) {
            $json = json_decode($failedJob->payload);
            $command = unserialize($json->data->command);
            $parameters = $command->getParameters();

            $csv->insertOne([$parameters['user_id']]);
        }

        $csv->output('mute-promotions-failed-jobs.csv');
    }
}
