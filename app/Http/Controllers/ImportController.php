<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use League\Csv\Reader;
use Illuminate\Http\Request;
use App\Jobs\ImportTurboVotePosts;
use Illuminate\Support\Facades\Storage;

use App\Services\Rogue;

class ImportController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Rogue $rogue)
    {
        $this->middleware('auth');

        $this->rogue = $rogue;
    }

    /*
     * Show the upload form.
     */
    public function show()
    {
        return view('pages.import');
    }

    /**
     * Import the uploaded file.
     *
     * @param  Request $request
     */
    public function store(Request $request)
    {
        $request->validate([
            'upload-file' => 'required|mimes:csv,txt',
            'import-type' => 'required',
        ]);

        // Push file to S3.
        $upload = $request->file('upload-file');
        $path = 'uploads/' . $request->input('import-type') . '-importer' . Carbon::now() . '.csv';
        $csv = Reader::createFromPath($upload->getRealPath());
        $success = Storage::put($path, (string)$csv);

        if (!$success) {
            throw new HttpException(500, 'Unable read and store file to S3.');
        }

        if ($request->input('import-type') === 'turbovote') {
            ImportTurboVotePosts::dispatch($path);
        }

        return redirect()->route('import.show')->with('status', 'Your CSV was added to the queue to be processed. Check out the progress on <a target="_blank" href="/horizon">Horizon</a>');
    }
}
