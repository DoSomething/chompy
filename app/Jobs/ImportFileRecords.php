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
     * The number of seconds the job can run before timing out.
     * We set this to 2 hours to avoid timeouts with the Mute Permutations CSVs (490K rows).
     *
     * @var int
     */
    public $timeout = 7200; // 60 seconds * 60 minutes * 2 hours

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
     * @param User $user - This could be null for machine-triggered imports.
     * @param string $filepath
     * @param string $importType
     * @param array $importOptions
     * @return void
     */
    public function __construct(
        \Chompy\User $user = null,
        string $filepath,
        string $importType,
        array $importOptions = []
    ) {
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
            'user_id' => optional($this->user)->northstar_id,
            'options' => $this->importOptions ? json_encode($this->importOptions) : null,
        ]);

        $importFile->save();

        foreach ($records as $offset => $record) {
            switch ($this->importType) {
                case ImportType::$rockTheVote:
                    ImportRockTheVoteRecord::dispatch($record, $importFile);
                    break;

                case ImportType::$emailSubscription:
                    ImportEmailSubscription::dispatch($record, $importFile, $this->importOptions);
                    break;

                case ImportType::$mutePromotions:
                    ImportMutePromotions::dispatch($record, $importFile);
                    break;
            }

            event(new LogProgress('', 'progress', ($offset / $this->totalRecords) * 100));
        }

        // Now that we've chomped, delete the import file.
        Storage::delete($this->filepath);

        event(new LogProgress('Done!', 'general'));
    }

    /**
     * Returns the parameters passed to this job.
     *
     * @return array
     */
    public function getParameters()
    {
        return [
            'import_type' => $this->importType,
            'options' => $this->importOptions,
            'user_id' => optional($this->user)->northstar_id,
        ];
    }
}
