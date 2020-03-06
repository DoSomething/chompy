<?php

namespace Chompy\Console\Commands;

use Carbon\Carbon;
use Chompy\ImportType;
use Illuminate\Console\Command;
use Chompy\Services\RockTheVote;
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
        $id = $this->argument('id');

        info('Checking status of report ' . $id);

        $report = app('Chompy\Services\RockTheVote')->getReportStatusById($id);

        info('Report ' . $id . ' status is ' . $report->status);

        if ($report->status === 'complete') {
            $this->downloadAndImport($id, $report->download_url);
        }
    }

    /**
     * @param int $id
     * @param string $url
     */
    private function downloadAndImport($id, $url) {
        $importType = ImportType::$rockTheVote;
        $path = 'uploads/' . $importType . '-report-' . $id . '-' . Carbon::now() . '.csv';

        Storage::put($path, app('Chompy\Services\RockTheVote')->getReportByUrl($url));

        info('Downloaded report '.$id);

        ImportFileRecords::dispatch(null, $path, $importType, ['report_id' => $id])->delay(now()->addSeconds(3));
    }
}
