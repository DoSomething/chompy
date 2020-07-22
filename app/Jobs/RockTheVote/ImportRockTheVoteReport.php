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

        info('Report ' . $reportId . ' status is ' . $status);

        if ($status !== 'complete') {
            $this->report->save();

            // If non-failed, status may be queued or building, so try importing again in 2 minutes. 
            if ($status !== 'failed') {
                return self::dispatch($this->user, $this->report)->delay(now()->addMinutes(2));
            }

            // If failed and we've already retried this report, log the oddity and discard this job.
            if ($this->report->retry_report_id) {
                info('Report ' . $reportId . ' already has retry report ' . $this->report->retry_report_id);

                return;
            }

            $retryReport = $this->report->createRetryReport();

            info('Report ' . $reportId . ' created retry report ' . $this->report->retry_report_id);

            return self::dispatch($this->user, $retryReport);
        }

        // Download the completed report CSV.
        $now = Carbon::now();
        $path = 'uploads/' . ImportType::$rockTheVote . '-report-' . $reportId . '-' .$now . '.csv';

        Storage::put($path, app(RockTheVote::class)->getReportByUrl($response->download_url));

        info('Downloaded report '.$reportId);

        // Import report CSV.
        ImportFileRecords::dispatch($this->user, $path, ImportType::$rockTheVote, ['report_id' => $reportId])->delay(now()->addSeconds(3));

        $this->report->dispatched_at = $now;
        $this->report->save();
    }

    /**
     * Returns the parameters passed to this job.
     *
     * @return array
     */
    public function getParameters()
    {
        return [
            'report' => $this->report,
            'user' => $this->user,
        ];
    }
}
