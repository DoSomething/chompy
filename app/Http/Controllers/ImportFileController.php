<?php

namespace Chompy\Http\Controllers;

use Chompy\Models\ImportFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;

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

    /**
     * Display a listing of import files.
     *
     * @return Response
     */
    public function index()
    {
        $data = ImportFile::paginate(50);

        info($data);

        return view('pages.import-files.index', ['data' => $data]);
    }
}
