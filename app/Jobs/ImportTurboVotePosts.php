<?php

namespace App\Jobs;

// use Illuminate\Bus\Queueable;
// use Illuminate\Queue\SerializesModels;
// use Illuminate\Queue\InteractsWithQueue;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Bus\Dispatchable;

use Carbon\Carbon;
use League\Csv\Reader;
// use Rogue\Models\Post;
// use Rogue\Models\Signup;
use Illuminate\Bus\Queueable;
// use Rogue\Services\Three\PostService;
use Illuminate\Support\Facades\Storage;
// use Rogue\Services\Three\SignupService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportTurboVotePosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The path to the stored csv.
     *
     * @var string
     */
    protected $filepath;

    /**
     * The role of the authenticated user that kicked off the request.
     *
     * @var object
     */
    protected $authenticatedUserRole;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($filepath)
    {
        $this->filepath = $filepath;

        // $this->authenticatedUserRole = $authenticatedUserRole;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $file = Storage::get($this->filepath);
        $csv = Reader::createFromString($file);
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        foreach ($records as $record) {
            info('Importing record ' . $record['id']);

            // $referralCode = $record['referral-code'];

            // if ($referralCode) {
            //     $referralCodeValues = $this->parseReferralCode(explode(',', $referralCode));

            //     // Fall back to the Grab The Mic campaign (campaign_id: 8017, campaign_run_id: 8022)
            //     // if these keys are not present.
            //     $referralCodeValues['campaign_id'] = !isset($referralCodeValues['campaign_id']) ? '8017' : $referralCodeValues['campaign_id'];
            //     $referralCodeValues['campaign_run_id'] = !isset($referralCodeValues['campaign_run_id']) ? '8022' : $referralCodeValues['campaign_run_id'];

            //     if (isset($referralCodeValues['northstar_id'])) {
            //         // Check if a signup exists already.
            //         // If the signup doesn't exist, create one.
            //         // Check if a post already exists.
            //         // If the post doesn't exist, create one.
            //         if (!$post) {
            //             $tvCreatedAtMonth = strtolower(Carbon::parse($record['created-at'])->format('F-Y'));
            //             $sourceDetails = isset($referralCodeValues['source_details']) ? $referralCodeValues['source_details'] : null;
            //             $postDetails = $this->extractDetails($record);

            //             $postData = [
            //                 'campaign_id' => $referralCodeValues['campaign_id'],
            //                 'northstar_id' => $referralCodeValues['northstar_id'],
            //                 'type' => 'voter-reg',
            //                 'action' => $tvCreatedAtMonth . '-turbovote',
            //                 'status' => $record['voter-registration-status'],
            //                 'source' => $referralCodeValues['source'],
            //                 'source_details' => $sourceDetails,
            //                 'details' => $postDetails,
            //             ];

            //             // $post = $postService->create($postData, $signup->id, $this->authenticatedUserRole);
            //         } else {
            //             // If a post already exists, check if status is the same on the CSV record and the existing post,
            //             // if not update the post with the new status.
            //             if ($record['voter-registration-status'] !== $post->status) {
            //                 $postService->update($post, ['status' => $record['voter-registration-status']]);
            //             }
            //         }
            //     } else {
            //         info('Skipped record ' . $record['id'] . ' because no northstar id or campaign id is available.');
            //     }
            // } else {
            //     info('Skipped record ' . $record['id'] . ' because no referral code is available.');
            // }
        }
    }
}

