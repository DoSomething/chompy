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
        $reportId = $this->argument('id');

        info('Checking status of report ' . $reportId);

        $report = app('Chompy\Services\RockTheVote')->getReportStatusById($reportId);

        info('Report ' . $reportId . ' status is ' . $report->status);

        if ($report->status === 'complete') {
            $this->downloadAndImport($reportId, $report->download_url);
        }
    }

    /**
     * Fetches Rock The Vote CSV at given URL for given report ID, and imports it.
     *
     * @param int $id
     * @param string $url
     */
    private function downloadAndImport($reportId, $url)
    {
        $path = 'uploads/' . ImportType::$rockTheVote . '-report-' . $reportId . '-' . Carbon::now() . '.csv';

        Storage::put($path, app('Chompy\Services\RockTheVote')->getReportByUrl($url));

        info('Downloaded report '.$reportId);

        ImportFileRecords::dispatch(null, $path, ImportType::$rockTheVote, ['report_id' => $reportId])->delay(now()->addSeconds(3));
    }
}
