<?php

namespace Tests\Http;

use Exception;
use Tests\TestCase;
use Chompy\Models\RockTheVoteReport;

class RockTheVoteReportTest extends TestCase
{
    /**
     * Test that expected fields are parsed from given API response.
     *
     * @return void
     */
    public function testCreateFromApiResponse()
    {
        $apiResponse = (object) [
            'status'=> 'queued',
            'status_url' => 'https://register.rockthevote.com/api/v4/registrant_reports/17',
        ];

        $report = RockTheVoteReport::createFromApiResponse($apiResponse, null, null);

        $this->assertEquals($report->id, 17);
        $this->assertEquals($report->status, 'queued');
        $this->assertEquals($report->user_id, null);
    }
}
