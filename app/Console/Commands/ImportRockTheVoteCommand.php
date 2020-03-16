<?php

namespace Chompy\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Chompy\Jobs\CreateRockTheVoteReport;

class ImportRockTheVoteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:rock-the-vote';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports Rock The Vote registrations from the past hour.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // We want all registrations that have started before the current datetime...
        $before = Carbon::now();
        $since = clone $before;

        // ... and after an hour ago (adding two overlapping minutes to ensure we don't miss any within the internal)
        CreateRockTheVoteReport::dispatch($since->subHours(1)->subMinutes(2), $before);
    }
}
