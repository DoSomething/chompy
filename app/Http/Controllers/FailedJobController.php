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
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $data = \DB::table('failed_jobs')->paginate(10);

        foreach ($data as $row) {
            $json = json_decode($row->payload);
            $row->commandName = $json->data->commandName;
            $row->errorMessage = Str::limit($row->exception, 512);

            if ($row->commandName === 'Chompy\Jobs\CreateCallPowerPostInRogue') {
                $command = unserialize($json->data->command);
                $row->parameters = $command->getParameters();
            }
        }

        return view('pages.failed-jobs', ['data' =>  $data]);
    }
}
