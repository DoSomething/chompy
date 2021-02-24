<?php

namespace Tests\Jobs\RockTheVote;

use Exception;
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

        $this->northstarMock->shouldReceive('post')
            ->with('v2/users/' . $userId . '/promotions')
            ->andReturn(['data' => [
                'id' => $userId,
                'promotions_muted_at' => now(),
            ]]);

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

    /**
     * Test that an error is thrown if the Northstar API request fails.
     *
     * @return void
     */
    public function testDoesNotLogMutePermissionsIfUserNotFound()
    {
        $userId = $this->faker->northstar_id;
        $importFile = factory(ImportFile::class)->create();

        // Gateway PHP returns a null value when resource not found.
        $this->northstarMock->shouldReceive('post')
            ->with('v2/users/' . $userId . '/promotions')
            ->andReturn(null);

        $this->expectException(Exception::class);

        $job = new ImportMutePromotions(
            ['northstar_id' => $userId],
            $importFile
        );
        $job->handle();

        $this->assertDatabaseMissing('mute_promotions_logs', [
            'import_file_id' => $importFile->id,
            'user_id' => $userId,
        ]);
    }
}
