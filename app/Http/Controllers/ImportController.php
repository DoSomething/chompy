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
    // /**
    //  * Create a new controller instance.
    //  *
    //  * @return void
    //  */
    // public function __construct(Rogue $rogue)
    // {
    //     $this->middleware('auth');
    //     $this->rogue = $rogue;

    //     $postData = [
    //         'campaign_id' => '1234',
    //         'northstar_id' => 'asidufasdf',
    //         'type' => 'voter-reg',
    //         'action' => 'chompy-turbovote',
    //         'status' => 'voter-registration-status',
    //         'source' => 'chompy',
    //         'source_details' => 'source_details',
    //         'details' => 'post_details',
    //         'text' => 'This should not be required',
    //     ];

    //                 // $rogue->asClient()->storePost($postData);
    //                 // $rogue->asClient()->send('GET', 'v3/posts?limit=35', [])
    //     $multipartData = collect($postData)->map(function ($value, $key) {
    //         return ['name' => $key, 'contents' => $value];
    //     })->values()->toArray();
    //     dd($multipartData);
    //     dd(token());
    //     $rogue->asClient()->send('POST', 'v3/posts', [
    //         'multipart' => $multipartData,
    //     ]);;

    // }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
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
