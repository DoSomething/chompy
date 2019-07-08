<?php

namespace Chompy\Http\Controllers;

class FailedJobController extends Controller
{
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
