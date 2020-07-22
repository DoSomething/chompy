<?php

namespace Tests\Jobs\RockTheVote;

use Tests\TestCase;
use Chompy\User;
use Chompy\Jobs\ImportFileRecords;
use Illuminate\Support\Facades\Bus;
use Chompy\Models\RockTheVoteReport;
use Illuminate\Support\Facades\Storage;
use Chompy\Jobs\ImportRockTheVoteReport;

class ImportRockTheVoteReportTest extends TestCase
{
    /**
     * Test that report is not downloaded and job is retried if status is building.
     *
     * @return void
     */
    public function testReportStatusBuilding()
    {
        Bus::fake();

        $report = factory(RockTheVoteReport::class)->create();
        $user = User::forceCreate(['role' => 'admin']);

        $this->rockTheVoteMock->shouldReceive('getReportStatusById')->andReturn((object) [
            'status'=> 'building',
            'record_count' => 117,
            'current_index' => 3,
        ]);
        $this->rockTheVoteMock->shouldNotReceive('getReportByUrl');

        $importRockTheVoteReportJob = new ImportRockTheVoteReport($user, $report);

        $importRockTheVoteReportJob->handle();

        $this->assertEquals($report->status, 'building');
        $this->assertEquals($report->row_count, 117);
        $this->assertEquals($report->current_index, 3);
        $this->assertEquals($report->user_id, $user->northstar_id);

        Bus::assertDispatched(ImportRockTheVoteReport::class, function ($job) use (&$report, &$user) {
            $params = $job->getParameters();

            return $params['report'] == $report && $params['user'] == $user;
        });

        Bus::assertNotDispatched(ImportFileRecords::class);
    }

    /**
     * Test that a new job is not dispatched if status is failed and report has a retry report ID.
     *
     * @return void
     */
    public function testReportStatusFailedAndRetried()
    {
        Bus::fake();

        $report = factory(RockTheVoteReport::class)->create([
            'retry_report_id' => 27,
        ]);
        $user = User::forceCreate(['role' => 'admin']);

        $this->rockTheVoteMock->shouldReceive('getReportStatusById')->andReturn((object) [
            'status'=> 'failed',
            'record_count' => 0,
            'current_index' => 0,
        ]);
        $this->rockTheVoteMock->shouldNotReceive('getReportByUrl');

        $importRockTheVoteReportJob = new ImportRockTheVoteReport($user, $report);

        $importRockTheVoteReportJob->handle();

        Bus::assertNotDispatched(ImportRockTheVoteReport::class);
    }

    /**
     * Test that a new report is dispatched if status is failed and report retry report ID is null.
     *
     * @return void
     */
    public function testReportStatusFailedAndNotRetried()
    {
        Bus::fake();

        $report = factory(RockTheVoteReport::class)->create();
        $user = User::forceCreate(['role' => 'admin']);

        $this->rockTheVoteMock->shouldReceive('getReportStatusById')->andReturn((object) [
            'status'=> 'failed',
            'record_count' => 0,
            'current_index' => 0,
        ]);
        $this->rockTheVoteMock->shouldReceive('createReport')->andReturn((object) [
            'status'=> 'queued',
            'status_url' => 'https://register.rockthevote.com/api/v4/registrant_reports/127',
        ]);
        $this->rockTheVoteMock->shouldNotReceive('getReportByUrl');

        $importRockTheVoteReportJob = new ImportRockTheVoteReport($user, $report);

        $importRockTheVoteReportJob->handle();

        Bus::assertDispatched(ImportRockTheVoteReport::class, function ($job) use (&$report, &$user) {
            $params = $job->getParameters();

            $this->assertEquals($params['report']->id, $report->retry_report_id);

            return true;
        });
    }

    /**
     * Test that report is downloaded and its contents are dispatched for import if status complete.
     *
     * @return void
     */
    public function testReportStatusComplete()
    {
        Bus::fake();
        Storage::fake();

        $report = factory(RockTheVoteReport::class)->create();
        $user = User::forceCreate(['role' => 'admin']);

        $this->rockTheVoteMock->shouldReceive('getReportStatusById')->andReturn((object) [
            'status'=> 'complete',
            'download_url' => 'https://register.rockthevote.com/api/v4/registrant_reports/17/download',
            'record_count' => 1112,
            'current_index' => 1112,
        ]);

        $this->rockTheVoteMock->shouldReceive('getReportByUrl');

        $importRockTheVoteReportJob = new ImportRockTheVoteReport($user, $report);

        $importRockTheVoteReportJob->handle();

        Bus::assertDispatched(ImportFileRecords::class, function ($job) use (&$report, &$user) {
            $params = $job->getParameters();

            $this->assertEquals($params['import_type'], \Chompy\ImportType::$rockTheVote);
            $this->assertEquals($params['options']['report_id'], $report->id);
            $this->assertEquals($params['user_id'], $user->northstar_id);

            return true;
        });

        $this->assertTrue(isset($report->dispatched_at));
        $this->assertEquals($report->status, 'complete');
    }
}
