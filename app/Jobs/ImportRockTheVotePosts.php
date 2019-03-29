<?php

namespace Chompy\Jobs;

use Chompy\ImportType;
use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Chompy\Events\LogProgress;
use Chompy\Traits\ImportToRogue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportRockTheVotePosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ImportToRogue;

    /**
     * The path to the stored csv.
     *
     * @var string
     */
    protected $filepath;

    /**
     * The count of the total records in the stored csv.
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
        return [ImportType::$rockTheVote];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Rogue $rogue)
    {
        info('STARTING RTV IMPORT');

        $records = $this->getCSVRecords($this->filepath);

        foreach ($records as $offset => $record) {
            ImportRockTheVoteRecord::dispatch($record);

            event(new LogProgress('', 'progress', ($offset / $this->totalRecords) * 100));
        }

        event(new LogProgress('Done!', 'general'));
    }
}
