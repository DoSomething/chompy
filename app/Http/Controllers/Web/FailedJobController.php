<?php

namespace Chompy\Http\Controllers\Web;

use Chompy\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

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
     * Display a listing of failed jobs.
     *
     * @return Response
     */
    public function index()
    {
        return view('pages.failed-jobs.index', [
            'data' => DB::table('failed_jobs')->paginate(10),
        ]);
    }

    /**
     * Display a failed job.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $data = DB::table('failed_jobs')->where('id', '=', $id)->get();

        if (! isset($data[0])) {
            abort(404);
        }

        return view('pages.failed-jobs.show', [
            'failedJob' => parse_failed_job($data[0]),
        ]);
    }

    /**
     * Delete a failed job.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $exitCode = Artisan::call('queue:forget', ['id' => $id]);

        info('Forgetting job:'.$id.' exitCode:'.$exitCode);

        return redirect('failed-jobs')
            ->with('status', 'Deleted job '.$id.' (exit code '.$exitCode.').');
    }
}
