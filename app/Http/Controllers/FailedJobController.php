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
            $row->json = json_decode($row->payload);
        }

        return \View::make('pages.failed-jobs')
            ->with('data', $data);
    }
}
