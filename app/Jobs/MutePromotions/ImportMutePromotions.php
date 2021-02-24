<?php

namespace Chompy\Jobs;

use Chompy\Models\ImportFile;
use Chompy\Models\MutePromotionsLog;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportMutePromotions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The Northstar user ID to mute promotions for.
     *
     * @var string
     */
    protected $userId;

    /**
     * Create a new job instance.
     *
     * @param array $record
     * @param ImportFile $importFile
     * @return void
     */
    public function __construct($record, ImportFile $importFile)
    {
        $this->userId = $record['northstar_id'];
        $this->importFile = $importFile;
    }

    /**
     * Execute the job to mute user promotions.
     */
    public function handle()
    {
        gateway('northstar')->asClient()->delete('v2/users/'. $this->userId . '/promotions');

        MutePromotionsLog::create([
            'import_file_id' => $this->importFile->id,
            'user_id' => $this->userId,
        ]);

        info('import.mute-promotions', ['user_id' => $this->userId]);

        $this->importFile->incrementImportCount();
    }

    /**
     * Return the parameters passed to this job.
     *
     * @return array
     */
    public function getParameters()
    {
        return [
            'import_file_id' => $this->importFile->id,
            'user_id' => $this->userId,
        ];
    }
}
