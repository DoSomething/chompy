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
     *
     */
    public function __construct(Rogue $rogue)
    {
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
            'importType' => 'required',
        ]);


        // Push file to S3.
        $upload = $request->file('upload-file');
        $path = 'test/files/' . $request->input('importType') . '-chompy-import' . Carbon::now() . '.csv';
        $csv = Reader::createFromPath($upload->getRealPath());
        $success = Storage::put($path, (string)$csv);
        // $contents = Storage::get($path);

        if (!$success) {
            throw new HttpException(500, 'Unable read and store file to S3.');
        }

        if ($request->input('importType') === 'turbovote') {
            // We need to pass the file path and authenticated user role to
            // the queue job because it does not have access to these things otherwise.
            ImportTurboVotePosts::dispatch($path);
        }

        return "Import that CSV";
    }
}
