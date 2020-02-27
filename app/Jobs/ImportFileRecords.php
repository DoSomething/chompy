<?php

namespace Chompy\Jobs;

use Chompy\ImportType;
use League\Csv\Reader;
use Chompy\Services\Rogue;
use Chompy\Models\ImportFile;
use Illuminate\Bus\Queueable;
use Chompy\Events\LogProgress;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportFileRecords implements ShouldQueue
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
     * The import options.
     *
     * @var array
     */
    protected $importOptions;

    /**
     * The count of the total records in the stored csv.
     *
     * @var array
     */
    protected $totalRecords;

    /**
     * Create a new job instance.
     *
     * @param string $filepath
     * @param string $importType
     * @param array $importOptions
     * @return void
     */
    public function __construct($user, $filepath, $importType, $importOptions)
    {
        $this->filepath = $filepath;
        $this->importType = $importType;
        $this->importOptions = $importOptions;
        $this->user = $user;
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
        info('STARTING '.$this->importType.' IMPORT', ['options' => print_r($this->importOptions, true)]);

        $records = $this->getRecords($this->filepath);

        $importFile = new ImportFile([
            'filepath' => $this->filepath,
            'import_type' => $this->importType,
            'row_count' => $this->totalRecords,
            'user_id' => $this->user ? $this->user->northstar_id : null,
        ]);

        $importFile->save();

        foreach ($records as $offset => $record) {
            if ($this->importType === ImportType::$rockTheVote) {
                ImportRockTheVoteRecord::dispatch($record, $importFile->id);
            } elseif ($this->importType === ImportType::$emailSubscription) {
                ImportEmailSubscription::dispatch($record, $this->importOptions['source_detail'], $this->importOptions['email_subscription_topic']);
            }
            event(new LogProgress('', 'progress', ($offset / $this->totalRecords) * 100));
        }

        // Now that we've chomped, delete the import file.
        Storage::delete($this->filepath);

        event(new LogProgress('Done!', 'general'));
    }
}
