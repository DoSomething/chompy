<?php

namespace Tests\Http\Web;

use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Chompy\Jobs\ImportRockTheVoteReport;

class RockTheVoteReportTest extends TestCase
{
    /**
     * Test creating and importing a Rock The Vote Report via web.
     *
     * @return void
     */
    public function testCreateRockTheReportFormSubmission()
    {
        Bus::fake();

        $this->rockTheVoteMock->shouldReceive('createReport')->andReturn((object) [
            'status'=> 'queued',
            'status_url' => 'https://register.rockthevote.com/api/v4/registrant_reports/17',
        ]);

        $admin = \Chompy\User::forceCreate(['role' => 'admin']);
        $response = $this->be($admin)->postJson('/rock-the-vote-reports', [
            'since' => '2019-12-19 00:00:00',
            'before' => '2020-02-19 00:00:00',
        ]);

        Bus::assertDispatched(ImportRockTheVoteReport::class, function ($job) use (&$admin) {
            $params = $job->getParameters();

            return $params['report']->id == 17 && $params['user'] == $admin;
        });

        // Verify redirect to new Rock the Vote report.
        $response->assertRedirect('/rock-the-vote-reports/17');
    }
}
