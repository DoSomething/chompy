<?php

namespace Tests\Http\Web;

use Tests\TestCase;
use Chompy\Jobs\ImportFileRecords;
use Illuminate\Support\Facades\Bus;
use Chompy\Models\RockTheVoteReport;
use Illuminate\Support\Facades\Storage;
use Chompy\Jobs\ImportRockTheVoteReport;

class ImportRockTheVoteReportTest extends TestCase
{
    /**
     * Test that report is not downloaded if status not complete.
     *
     * @return void
     */
    public function testReportStatusNotComplete()
    {
        Bus::fake();

        $report = factory(RockTheVoteReport::class)->create();

        $this->rockTheVoteMock->shouldReceive('getReportStatusById')->andReturn((object) [
            'status'=> 'merging',
            'record_count' => 117,
            'current_index' => 3,
        ]);
        $this->rockTheVoteMock->shouldNotReceive('getReportByUrl');

        $job = new ImportRockTheVoteReport(null, $report);

        $job->handle();

        $this->assertEquals($report->status, 'merging');
        $this->assertEquals($report->row_count, 117);
        $this->assertEquals($report->current_index, 3);

        Bus::assertNotDispatched(ImportFileRecords::class, function () {
            return true;
        });
    }

    /**
     * Test that report is downloaded when status is complete..
     *
     * @return void
     */
    public function testReportStatusComplete()
    {
        Bus::fake();
        Storage::fake();

        $report = factory(RockTheVoteReport::class)->create();

        $this->rockTheVoteMock->shouldReceive('getReportStatusById')->andReturn((object) [
            'status'=> 'complete',
            'download_url' => 'https://register.rockthevote.com/api/v4/registrant_reports/17/download',
            'record_count' => 1112,
            'current_index' => 1112,
        ]);

        $this->rockTheVoteMock->shouldReceive('getReportByUrl');

        $job = new ImportRockTheVoteReport(null, $report);

        $job->handle();

        Bus::assertDispatched(ImportFileRecords::class, function ($job) {
            return true;
        });

        $this->assertTrue(isset($report->dispatched_at));
        $this->assertEquals($report->status, 'complete');
    }
}
