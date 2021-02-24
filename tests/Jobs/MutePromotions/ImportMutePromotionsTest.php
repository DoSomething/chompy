<?php

namespace Tests\Jobs\RockTheVote;

use Tests\TestCase;
use Chompy\Models\ImportFile;
use Chompy\Jobs\ImportMutePromotions;

class ImportMutePromotionsTest extends TestCase
{
    /**
     * Test that Northstar Mute Promotions request is executed and logged.
     *
     * @return void
     */
    public function testExecutesMutePromotionsNorthstarRequest()
    {
        $userId = $this->faker->northstar_id;
        $importFile = factory(ImportFile::class)->create();

        $this->northstarMock->shouldReceive('delete')->with('v2/users/' . $userId . '/promotions');

        $job = new ImportMutePromotions(
            ['northstar_id' => $userId],
            $importFile
        );
        $job->handle();

        $this->assertDatabaseHas('mute_promotions_logs', [
            'import_file_id' => $importFile->id,
            'user_id' => $userId,
        ]);
    }
}
