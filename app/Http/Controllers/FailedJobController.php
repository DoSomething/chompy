<?php

namespace Chompy\Http\Controllers;

use Illuminate\Support\Str;

class FailedJobController extends Controller
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
     * Adds parsed properties to given failed job class.
     *
     * @param stdClass $failedJob
     *
     * @return void
     */
    protected function addParsedPropertiesToFailedJob($failedJob)
    {
        $json = json_decode($failedJob->payload);
        $failedJob->commandName = $json->data->commandName;
        $failedJob->errorMessage = Str::limit($failedJob->exception, 255);

        if ($failedJob->commandName === 'Chompy\Jobs\CreateCallPowerPostInRogue') {
            $command = unserialize($json->data->command);
            $failedJob->parameters = $command->getParameters();
        }
    }

    /**
     * Display a listing of failed jobs.
     *
     * @return Response
     */
    public function index()
    {
        $data = \DB::table('failed_jobs')->paginate(10);

        foreach ($data as $failedJob) {
            $this->addParsedPropertiesToFailedJob($failedJob);
        }

        return view('pages.failed-jobs.index', ['data' => $data]);
    }

    /**
     * Display a failed job.
     *
     * @return Response
     */
    public function show($id)
    {
        $data = \DB::table('failed_jobs')->where('id', '=', $id)->get();
        $failedJob = $data[0];
        $this->addParsedPropertiesToFailedJob($failedJob);

        return view('pages.failed-jobs.show', ['data' => $failedJob]);
    }
}
