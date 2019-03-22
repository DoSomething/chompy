<?php

namespace Chompy\Jobs;

use Illuminate\Bus\Queueable;
use Chompy\Events\LogProgress;
use Chompy\Traits\ImportToRogue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Chompy\Jobs\CreateTurboVotePostInRogue;

class ImportTurboVotePosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ImportToRogue;

    /**
     * The path to the stored csv.
     *
     * @var string
     */
    protected $filepath;

    /**
     * The total records in the stored csv.
     *
     * @var array
     */
    protected $totalRecords;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($filepath)
    {
        $this->filepath = $filepath;
    }


    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags()
    {
        return ['turbovote'];
    }

    /**
     * Execute the job.
     *
     * @param  Rogue $rogue
     * @return void
     */
    public function handle()
    {
        // @TODO: We need to write some tests for this import!
        info('getting records');

        $records = $this->getCSVRecords($this->filepath);

        info('records received');

        foreach ($records as $offset => $record) {
            CreateTurboVotePostInRogue::dispatch($record);

            event(new LogProgress('', 'progress', ($offset / $this->totalRecords) * 100));
        }

        event(new LogProgress('Done!', 'general'));
    }
}
