<?php

namespace Chompy\Http\Controllers;

use Carbon\Carbon;
use Chompy\ImportType;
use League\Csv\Reader;
use Illuminate\Http\Request;
use Chompy\Jobs\ImportTurboVotePosts;
use Chompy\Jobs\ImportRockTheVotePosts;
use Illuminate\Support\Facades\Storage;
use Chompy\Jobs\ImportFacebookSharePosts;

class ImportController extends Controller
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

    /*
     * Show the upload form.
     */
    public function show($importType)
    {
        return view('pages.import', [
            'type' => $importType,
            'postConfig' => config('import.rock_the_vote.post'),
            'resetConfig' => config('import.rock_the_vote.reset'),
            'userConfig' => config('import.rock_the_vote.user'),
        ]);
    }

    /**
     * Import the uploaded file.
     *
     * @param  Request $request
     */
    public function store(Request $request, $importType)
    {
        $request->validate([
            'upload-file' => 'required|mimes:csv,txt',
        ]);

        // Push file to S3.
        $upload = $request->file('upload-file');

        $path = 'uploads/' . $importType . '-importer' . Carbon::now() . '.csv';
        $csv = Reader::createFromPath($upload->getRealPath());
        $success = Storage::put($path, (string) $csv);

        if (! $success) {
            throw new HttpException(500, 'Unable read and store file to S3.');
        }

        if ($importType === ImportType::$turbovote) {
            info('turbo vote import happening');
            ImportTurboVotePosts::dispatch($path)->delay(now()->addSeconds(3));
        }

        if ($importType === ImportType::$rockTheVote) {
            info('rock the vote import happening');
            ImportRockTheVotePosts::dispatch($path)->delay(now()->addSeconds(3));
        }

        if ($importType === ImportType::$facebook) {
            info('Facebook share import happening');
            ImportFacebookSharePosts::dispatch($path)->delay(now()->addSeconds(3));
        }

        return redirect()->route('import/'.$importType)
            ->with('status', 'Your CSV was added to the queue to be processed.');
    }
}
