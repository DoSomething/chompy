<?php

namespace Chompy\Http\Controllers\Web;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;

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

        if (Str::contains($failedJob->commandName, 'CallPower') || Str::contains($failedJob->commandName, 'SoftEdge')) {
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
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $data = \DB::table('failed_jobs')->where('id', '=', $id)->get();
        if (! isset($data[0])) {
            abort(404);
        }

        $failedJob = $data[0];
        $this->addParsedPropertiesToFailedJob($failedJob);

        return view('pages.failed-jobs.show', ['data' => $failedJob]);
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
