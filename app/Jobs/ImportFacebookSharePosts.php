<?php

namespace Chompy\Jobs;

use League\Csv\Reader;
use Chompy\Services\Rogue;
use Illuminate\Bus\Queueable;
use Chompy\Events\LogProgress;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportFacebookSharePosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The path to the stored csv.
     *
     * @var string
     */
    protected $filepath;

    /**
     * Stat counter.
     */
    protected $stats;

    /**
     * Total record counter.
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

        $this->stats = $this->statsInit();
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags()
    {
        return ['facebook-share'];
    }

    /**
     * Execute the job.
     *
     * @param Rogue $rogue
     * @return void
     */
    public function handle(Rogue $rogue)
    {
        $records = $this->getCSVRecords($this->filepath);

        foreach ($records as $offset => $record) {
            info('process_log: Processing: ' . $record['_id']);
            $postData = [
                'campaign_id' => (int) $record['campaign_id'],
                'campaign_run_id' => (int) $record['campaign_run'],
                'northstar_id' => $record['user.northstarId'],
                'type' => 'share-social',
                'action' => 'quiz-share',
                'details' => json_encode(['platform' => 'facebook']),
                'status' => 'accepted',
                'source' => 'importer-client',
                'source_detail' => json_encode(['original-source' => $record['event.source']]),
                'created_at' => $record['meta.timestamp'],
            ];

            try {
                $post = $rogue->createPost($postData);

                if ($post['data']) {
                    $this->stats['countPostCreated']++;
                }
            } catch (\Exception $e) {
                info('There was an error storing the post for: ' . $record['_id'], [
                    'Error' => $e->getMessage(),
                ]);
            }
            dd($post);
        }
    }

    /**
     * Initiate the stat counters.
     *
     * @return array
     */
    private function statsInit()
    {
        return [
            'totalRecords' => 0,
            'countScrubbed' => 0,
            'countProcessed' => 0,
            'countPostCreated' => 0,
            'countUserAccountsCreated' => 0,
        ];
    }


    private function getCSVRecords($filepath)
    {
        $file = Storage::get($filepath);
        $csv = Reader::createFromString($file);
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();
        $this->totalRecords = count($csv);

        event(new LogProgress('Total rows to chomp: ' . $this->totalRecords, 'general'));
        event(new LogProgress('', 'progress', 0));

        $this->stats['totalRecords'] = $this->totalRecords;

        return $records;
    }
}
