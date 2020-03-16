<?php

namespace Chompy\Jobs;

use Illuminate\Bus\Queueable;
use Chompy\Models\RockTheVoteReport;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateRockTheVoteReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The since parameter to create a Rock The Vote report with.
     *
     * @var DateTime
     */
    protected $since;

    /**
     * The before parameter to create a Rock The Vote report with.
     *
     * @var DateTime
     */
    protected $before;

    /**
     * Create a new job instance.
     *
     * @param DateTime $since
     * @param DateTime $before
     * @return void
     */
    public function __construct($since, $before)
    {
        $this->since = $since;
        $this->before = $before;
    }

    /**
     * Execute the job to create a Rock The Vote report and import it after creation.
     *
     * @return array
     */
    public function handle()
    {
        info('Creating report', ['before' => $this->before, 'since' => $this->since]);

        $report = RockTheVoteReport::createViaApi($this->since, $this->before);

        ImportRockTheVoteReport::dispatch(null, $report);
    }

    /**
     * Returns the parameters passed to this job.
     *
     * @return array
     */
    public function getParameters()
    {
        return [
            'since' => $this->since,
            'before' => $this->before,
        ];
    }
}
