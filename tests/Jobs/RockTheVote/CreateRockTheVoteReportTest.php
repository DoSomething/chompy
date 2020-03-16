<?php

namespace Tests\Console;

use Exception;
use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Chompy\Jobs\CreateRockTheVoteReport;
use Chompy\Jobs\ImportRockTheVoteReport;

class CreateRockTheVoteReportTest extends TestCase
{
    /**
     * Test that report is dispatched for import upon successful creation.
     *
     * @return void
     */
    public function testImportsReportUponCreateReportSuccess()
    {
        Bus::fake();

        $this->rockTheVoteMock->shouldReceive('createReport')->andReturn((object) [
            'status'=> 'queued',
            'status_url' => 'https://register.rockthevote.com/api/v4/registrant_reports/17',
        ]);

        $createRockTheVoteReportJob = new CreateRockTheVoteReport('2020-03-08 00:00:00', '2020-03-08 00:01:00');

        $createRockTheVoteReportJob->handle();

        $createParams = $createRockTheVoteReportJob->getParameters();

        Bus::assertDispatched(ImportRockTheVoteReport::class, function ($job) use (&$createParams) {
            $importParams = $job->getParameters();

            return $importParams['report'] == $createParams['report'];
        });
    }

    /**
     * Test that report is not dispatched for import upon failed creation.
     *
     * @return void
     */
    public function testDoesNotImportReportUponCreateReportFailure()
    {
        Bus::fake();

        $this->rockTheVoteMock->shouldReceive('createReport')->andThrow(new Exception(500));

        $createRockTheVoteReportJob = new CreateRockTheVoteReport('2020-03-08 00:00:00', '2020-03-08 00:01:00');

        $this->expectException(Exception::class);

        $createRockTheVoteReportJob->handle();

        Bus::assertNotDispatched(ImportRockTheVoteReport::class);
    }
}
