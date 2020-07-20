<?php

namespace Tests\Unit\Models;

use ErrorException;
use Tests\TestCase;
use Chompy\Models\RockTheVoteReport;

class RockTheVoteReportTest extends TestCase
{
    /**
     * Whether the fake RTV API configuration variable is set.
     *
     * @var bool
     */
    protected $isFakerEnabled;

    public function setUp(): void
    {
        parent::setUp();

        $this->isFakerEnabled = config('services.rock_the_vote.faker');

        if ($this->isFakerEnabled) {
            \Config::set('services.rock_the_vote.faker', false);
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();

        if ($this->isFakerEnabled) {
            \Config::set('services.rock_the_vote.faker', 'true');
        }
    }

    /**
     * Test that expected fields are parsed from given API response object.
     *
     * @return void
     */
    public function testCreateViaApiWithValidResponse()
    {
        $this->rockTheVoteMock->shouldReceive('createReport')->andReturn((object) [
            'status'=> 'queued',
            'status_url' => 'https://register.rockthevote.com/api/v4/registrant_reports/17',
        ]);

        $since = '2019-12-19 00:00:00';
        $before = '2020-02-19 00:00:00';

        $report = RockTheVoteReport::createViaApi($since, $before);

        $this->assertEquals($report->id, 17);
        $this->assertEquals($report->since, $since);
        $this->assertEquals($report->before, $before);
        $this->assertEquals($report->status, 'queued');
        $this->assertEquals($report->user_id, null);
    }

    /**
     * Test that an exception is thrown if a string is passed.
     *
     * @return void
     */
    public function testCreateViaApiWithInvalidResponseType()
    {
        $this->rockTheVoteMock->shouldReceive('createReport')->andReturn('test');

        $this->expectException(ErrorException::class);

        RockTheVoteReport::createViaApi();
    }

    /**
     * Test that an exception is thrown if a status_url is missing.
     *
     * @return void
     */
    public function testCreateViaApiWithMissingStatusUrl()
    {
        $this->rockTheVoteMock->shouldReceive('createReport')->andReturn((object) [
            'status'=> 'queued',
        ]);

        $this->expectException(ErrorException::class);

        RockTheVoteReport::createViaApi();
    }

    /**
     * Test that createRetryReport sets the retry_report_id after creating a report with the same
     * since and before parameters.
     *
     * @return void
     */
    public function testCreateRetryReport()
    {
        $params = [
            'before' => '2020-02-19 00:00:00',
            'since' => '2019-12-19 00:00:00',
        ];

        $report = factory(RockTheVoteReport::class)->create($params);

        $this->assertEquals($report->retry_report_id, null);

        $this->rockTheVoteMock->shouldReceive('createReport')->with($params)->andReturn((object) [
            'status'=> 'queued',
            'status_url' => 'https://register.rockthevote.com/api/v4/registrant_reports/17',
        ]);

        $retryReport = $report->createRetryReport();

        $this->assertEquals($report->retry_report_id, $retryReport->id);
    }
}
