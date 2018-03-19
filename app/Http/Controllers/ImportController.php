<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use League\Csv\Reader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
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
        $path = 'uploads/files/' . $request->input('importType') . '-import' . Carbon::now() . '.csv';
        $csv = Reader::createFromPath($upload->getRealPath());
        $success = Storage::put($path, (string)$csv);

        if (!$success) {
            throw new HttpException(500, 'Unable read and store file to S3.');
        }

        if ($request->input('importType') === 'turbovote') {
            // We need to pass the file path and authenticated user role to
            // the queue job because it does not have access to these things otherwise.
            ImportTurboVotePosts::dispatch($path, auth()->user()->role);
        }

        return "Import that CSV";
    }
}
