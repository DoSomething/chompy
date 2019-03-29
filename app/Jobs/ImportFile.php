<?php

namespace Chompy\Jobs;

use Chompy\ImportType;
use League\Csv\Reader;
use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Chompy\Events\LogProgress;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The path to the stored csv.
     *
     * @var string
     */
    protected $filepath;

    /**
     * The import type.
     *
     * @var string
     */
    protected $importType;

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
    public function __construct($filepath, $importType)
    {
        $this->filepath = $filepath;
        $this->importType = $importType;
    }

    /**
     * Get the tags that should be assigned to the job.
     * TODO: Is this used anywhere?
     *
     * @return array
     */
    public function tags()
    {
        return [$this->importType];
    }

    /**
     * Fetch records from the filepath.
     *
     * @return array
     */
    public function getRecords()
    {
        $file = Storage::get($this->filepath);
        $file = str_replace("\r", "\n", $file);

        $csv = Reader::createFromString($file);
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();
        $this->totalRecords = count($csv);

        event(new LogProgress('Total rows to chomp: ' . $this->totalRecords, 'general'));
        event(new LogProgress('', 'progress', 0));

        return $records;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Rogue $rogue)
    {
        info('STARTING '.$this->importType.' IMPORT');

        $records = $this->getRecords($this->filepath);

        foreach ($records as $offset => $record) {
            if ($this->importType === ImportType::$rockTheVote) {
                ImportRockTheVoteRecord::dispatch($record);
            }
            if ($this->importType === ImportType::$emailSubscription) {
                ImportEmailSubscription::dispatch($record['email']);
            }
            event(new LogProgress('', 'progress', ($offset / $this->totalRecords) * 100));
        }

        event(new LogProgress('Done!', 'general'));
    }
}
