<?php

namespace Chompy\Http\Controllers\Web;

use Carbon\Carbon;
use Chompy\ImportType;
use League\Csv\Reader;
use Illuminate\Http\Request;
use Chompy\Models\ImportFile;
use Chompy\Models\RockTheVoteLog;
use Chompy\Jobs\ImportFileRecords;
use Chompy\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ImportFileController extends Controller
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
     *
     * @param string $importType
     */
    public function create($importType)
    {
        return view('pages.import-files.create', [
            'importType' => $importType,
            'config' => ImportType::getConfig($importType),
        ]);
    }

    /**
     * Import the uploaded file.
     *
     * @param Request $request
     * @param string $importType
     */
    public function store(Request $request, $importType)
    {
        $importOptions = [];
        $rules = [
            'upload-file' => 'required|mimes:csv,txt',
        ];

        if ($importType === ImportType::$emailSubscription) {
            $rules['source-detail'] = 'required';
            $rules['topic'] = 'required';
            $importOptions = [
                'email_subscription_topic' => $request->input('topic'),
                'source_detail' => $request->input('source-detail'),
            ];
        }

        $request->validate($rules);

        // Push file to S3.
        $upload = $request->file('upload-file');

        $path = 'uploads/' . $importType . '-importer' . Carbon::now() . '.csv';
        $csv = Reader::createFromPath($upload->getRealPath());
        $success = Storage::put($path, (string) $csv);

        if (! $success) {
            throw new HttpException(500, 'Unable read and store file to S3.');
        }

        ImportFileRecords::dispatch(\Auth::user(), $path, $importType, $importOptions)->delay(now()->addSeconds(3));

        return redirect('import/'.$importType)
            ->with('status', 'Queued '.$path.' for import.');
    }

    /**
     * Display a listing of import files.
     *
     * @return Response
     */
    public function index()
    {
        $data = ImportFile::orderBy('id', 'desc')->paginate(15);

        return view('pages.import-files.index', ['data' => $data]);
    }

    /**
     * Display an import file.
     *
     * @return Response
     */
    public function show($id)
    {
        $importFile = ImportFile::findOrFail($id);
        $rows = [];

        if ($importFile->import_type === ImportType::$rockTheVote) {
            $rows = RockTheVoteLog::where('import_file_id', $id)->paginate(15);
        }

        return view('pages.import-files.show', ['importFile' => $importFile, 'rows' => $rows]);
    }
}