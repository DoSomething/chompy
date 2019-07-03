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
        $data = \DB::table('failed_jobs')->paginate(5);;

        return \View::make('pages.home')
            ->with('data', $data);
    }
}
