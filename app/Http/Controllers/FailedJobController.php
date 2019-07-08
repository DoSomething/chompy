<?php

namespace Chompy\Http\Controllers;

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
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $data = \DB::table('failed_jobs')->paginate(10);
        foreach ($data as $row) {
            $json = json_decode($row->payload);
            $row->command = unserialize($json->data->command);
            $row->commandName = $json->data->commandName;
            if ($row->commandName === 'Chompy\Jobs\CreateCallPowerPostInRogue') {
                $row->parameters = $row->command->getParameters();
            }
        }

        return \View::make('pages.failed-jobs')
            ->with('data', $data);
    }
}
