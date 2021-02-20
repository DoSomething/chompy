<?php

namespace Chompy\Jobs;

use Carbon\Carbon;
use Chompy\Models\ImportFile;
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
     * Execute the job to set user's promotions_muted_at field.
     *
     * @return array
     */
    public function handle()
    {
        $user = gateway('northstar')->asClient()->updateUser($this->userId, [
            'promotions_muted_at' => Carbon::now(),
        ]);

        info('import.mute-promotions', ['promotions_muted_at' => $user->promotions_muted_at, 'user_id' => $this->userId]);

        $this->importFile->incrementImportCount();
    }
}
