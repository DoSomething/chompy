<?php

namespace Chompy\Http\Controllers\Web;

use Carbon\Carbon;
use Chompy\ImportType;
use League\Csv\Reader;
use Illuminate\Http\Request;
use Chompy\Models\ImportFile;
use Chompy\Models\MutePromotionsLog;
use Chompy\Models\RockTheVoteLog;
use Chompy\Jobs\ImportFileRecords;
use Chompy\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Chompy\Jobs\ImportRockTheVoteRecord;

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
        $config = ImportType::getConfig($importType);

        if (request('source') !== 'test') {
            return view('pages.import-files.create', [
                'importType' => $importType,
                'config' => $config,
            ]);
        }

        $data = [];

        if ($importType === ImportType::$rockTheVote) {
            $userId = \Auth::user()->northstar_id;
            $user = gateway('northstar')->asClient()->getUser($userId, [
                'addr_street1', 'addr_street2', 'email', 'mobile', 'last_name',
            ]);

            $data = [
                'addr_street1' => $user->addr_street1,
                'addr_street2' => $user->addr_street2,
                'addr_city' => $user->addr_city,
                'addr_zip' => $user->addr_zip,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->mobile,
                'tracking_source' => 'source:test,source_details:ChompyUI',
                'started_registration' => Carbon::now()->format('Y-m-d H:i:s O'),
            ];
        }

        return view('pages.import-files.test', [
            'importType' => $importType,
            'config' => $config,
            'data' => $data,
        ]);
    }

    /**
     * Import the uploaded file.
     *
     * @param Request $request
     * @param string $importType
     */
    public function upload(Request $request, $importType)
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

        $upload = $request->file('upload-file');
        // Save original file name to reference from admin UI.
        $importOptions['name'] = $upload->getClientOriginalName();

        // Push file to S3.
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
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $importType = $request->query('type');

        $query = ImportFile::orderBy('id', 'desc');

        if ($importType) {
            $query->where('import_type', $importType);
        }

        return view('pages.import-files.index', ['data' => $query->paginate(15)]);
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

        switch ($importFile->import_type) {
            case ImportType::$mutePromotions:
                $rows = MutePromotionsLog::where('import_file_id', $id)->paginate(100);
                break;

            case ImportType::$rockTheVote:
                $rows = RockTheVoteLog::where('import_file_id', $id)->paginate(15);
                break;
        }

        return view('pages.import-files.show', [
            'importFile' => $importFile,
            'rows' => $rows,
        ]);
    }

    /**
     * Imports an create request.
     *
     * @param Request $request
     * @param string $importType
     */
    public function store(Request $request, $importType)
    {
        $result = [];

        if ($importType === ImportType::$rockTheVote) {
            $row = [
                'Email address' => $request->input('email'),
                'Finish with State' => $request->input('finish_with_state') ?: 'No',
                'First name' => $request->input('first_name'),
                'Home address' => $request->input('addr_street1'),
                'Home city' => $request->input('addr_city'),
                'Home unit' => $request->input('addr_street2'),
                'Home zip code' => $request->input('addr_zip'),
                'Last name' => $request->input('last_name'),
                'Opt-in to Partner SMS/robocall' => $request->input('sms_opt_in') ?: 'No',
                'Opt-in to Partner email?' => $request->input('email_opt_in') ?: 'No',
                'Phone' => $request->input('phone'),
                'Pre-Registered' => $request->input('pre_registered') ?: 'No',
                'Started registration' => $request->input('started_registration'),
                'Status' => $request->input('status'),
                'Tracking Source' => $request->input('tracking_source'),
            ];

            $importFile = new ImportFile();

            $importFile->user_id = \Auth::user()->northstar_id;
            $importFile->row_count = 1;
            $importFile->filepath = 'n/a';
            $importFile->import_type = $importType;
            $importFile->save();

            $result = array_merge([
                'import' => ['id' => $importFile->id],
            ], ImportRockTheVoteRecord::dispatchNow($row, $importFile));
        }

        return redirect('import/'.$importType.'?source=test')
            ->withInput($request->input())
            ->with('status', $result);
    }
}
