<?php

namespace Chompy\Console\Commands;

use Carbon\Carbon;
use Chompy\ImportType;
use Illuminate\Console\Command;
use Chompy\Jobs\ImportFileRecords;
use Illuminate\Support\Facades\Storage;

class ImportRockTheVoteReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rtv:import {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a Rock The Vote Report by Report ID';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = app('Chompy\Services\RockTheVote');
        $reportId = $this->argument('id');
        $importType = ImportType::$rockTheVote;
        $path = 'uploads/' . $importType . '-report-' . $reportId . '-' . Carbon::now() . '.csv';
        $success = Storage::put($path, $client->downloadReportById($reportId));

        info('Downloaded report '.$reportId);

        ImportFileRecords::dispatch(null, $path, $importType, ['report_id' => $reportId])->delay(now()->addSeconds(3));
    }
}
