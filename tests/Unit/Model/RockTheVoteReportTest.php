<?php

namespace Tests\Http;

use ErrorException;
use Tests\TestCase;
use Chompy\Models\RockTheVoteReport;

class RockTheVoteReportTest extends TestCase
{
    /**
     * Test that expected fields are parsed from given API response object.
     *
     * @return void
     */
    public function testCreateFromApiResponseWithValidResponse()
    {
        $apiResponse = (object) [
            'status'=> 'queued',
            'status_url' => 'https://register.rockthevote.com/api/v4/registrant_reports/17',
        ];
        $since = '2019-12-19 00:00:00';
        $before = '2020-02-19 00:00:00';

        $report = RockTheVoteReport::createFromApiResponse($apiResponse, $since, $before);

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
    public function testCreateFromApiResponseWithInvalidResponseType()
    {
        // Passing a string as the API response should throw an exception.
        $this->expectException(ErrorException::class);

        RockTheVoteReport::createFromApiResponse('test', null, null);
    }

    /**
     * Test that an exception is thrown if a status_url is missing.
     *
     * @return void
     */
    public function testCreateFromApiResponseWithMissingStatusUrl()
    {
        // A missing status_url in the API response should throw an exception.
        $this->expectException(ErrorException::class);

        $apiResponse = (object) [
            'status'=> 'queued',
        ];

        RockTheVoteReport::createFromApiResponse($apiResponse, null, null);
    }
}
