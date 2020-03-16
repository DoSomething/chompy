<?php

namespace Tests\Console;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Chompy\Jobs\CreateRockTheVoteReport;

class ImportRockTheVoteCommandTest extends TestCase
{
    public function testCreatesRockTheVoteReport()
    {
        Bus::fake();

        // Timestamp right before running the command.
        $startTime = $this->mockTime(Carbon::now());

        // Run the RTV import command.
        $this->artisan('import:rock-the-vote');

        Bus::assertDispatched(CreateRockTheVoteReport::class, function ($job) use (&$startTime) {
            $params = $job->getParameters();

            return $params['before'] == $startTime && $params['since'] == $startTime->subHours(1)->subMinutes(2);
        });
    }
}
