<?php

namespace Chompy\Jobs;

use Carbon\Carbon;
use Chompy\ImportType;
use Illuminate\Bus\Queueable;
use Chompy\Services\RockTheVote;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportRockTheVoteReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The report to download and import.
     *
     * @var RockTheVoteReport
     */
    protected $report;

    /**
     * Create a new job instance.
     *
     * @param RockTheVoteReport $report
     * @return void
     */
    public function __construct(\Chompy\User $user = null, \Chompy\Models\RockTheVoteReport $report)
    {
        $this->user = $user;
        $this->report = $report;
    }

    /**
     * Execute the job to check report status and download and import if complete.
     *
     * @return array
     */
    public function handle()
    {
        $reportId = $this->report->id;

        info('Checking status of report ' . $reportId);

        $response = app(RockTheVote::class)->getReportStatusById($reportId);
        $status = $response->status;

        $this->report->status = $status;
        $this->report->row_count = $response->record_count;
        $this->report->current_index = $response->current_index;

        info('Report status is ' . $status);

        if ($status !== 'complete') {
            $this->report->save();

            return self::dispatch($this->user, $this->report)->delay(now()->addMinutes(2));
        }

        $now = Carbon::now();
        $path = 'uploads/' . ImportType::$rockTheVote . '-report-' . $reportId . '-' .$now . '.csv';

        Storage::put($path, app(RockTheVote::class)->getReportByUrl($response->download_url));

        info('Downloaded report '.$reportId);

        ImportFileRecords::dispatch($this->user, $path, ImportType::$rockTheVote, ['report_id' => $reportId])->delay(now()->addSeconds(3));

        $this->report->dispatched_at = $now;
        $this->report->save();
    }
}
