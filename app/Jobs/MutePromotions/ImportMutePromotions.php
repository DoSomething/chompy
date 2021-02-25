<?php

namespace Chompy\Jobs;

use Exception;
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
     * The number of seconds the job can run before timing out.
     * We set this to 12 minutes because each of these files is about 490K rows, and will time out.
     *
     * @var int
     */
    public $timeout = 720;

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
        /*
         * We're sending a POST request instead of DELETE because Gateway PHP doesn't properly
         * parse a Northstar DELETE request.
         */
        $response = gateway('northstar')->asClient()->post('v2/users/'. $this->userId . '/promotions');

        if (! $response) {
            throw new Exception('Could not mute promotions for user ' . $this->userId);
        }

        MutePromotionsLog::create([
            'import_file_id' => $this->importFile->id,
            'user_id' => $this->userId,
        ]);

        info('import.mute-promotions', [
            'user_id' => $this->userId,
            'promotions_muted_at' => $response['data']['promotions_muted_at'],
        ]);

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
