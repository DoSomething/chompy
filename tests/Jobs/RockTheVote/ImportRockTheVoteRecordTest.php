<?php

namespace Tests\Jobs\RockTheVote;

use Tests\TestCase;
use Chompy\Jobs\ImportRockTheVoteRecord;

class ImportRockTheVoteRecordTest extends TestCase
{
    /**
     * Test that user and post are created if user not found.
     *
     * @return void
     */
    public function testCreatesUserIfUserNotFound()
    {
        $this->northstarMock->shouldReceive('getUser')->andReturn(null);
        $this->mockCreateNorthstarUser();
        $this->northstarMock->shouldReceive('sendPasswordReset');
        $this->rogueMock->shouldReceive('getPost')->andReturn(null);
        $this->rogueMock->shouldReceive('createPost')->andReturn([
            'data' => [
                'id' => $this->faker->randomDigitNotNull,
            ],
        ]);

        ImportRockTheVoteRecord::dispatch($this->faker->rockTheVoteReportRow(), $this->faker->randomDigitNotNull);
    }

    /**
     * Test that user is not created if user is found.
     *
     * @return void
     */
    public function testDoesNotCreateUserIfUserFound()
    {
        $this->mockGetNorthstarUser();
        $this->northstarMock->shouldNotReceive('createUser');
        $this->northstarMock->shouldNotReceive('sendPasswordReset');
        $this->rogueMock->shouldReceive('getPost')->andReturn(null);
        $this->rogueMock->shouldReceive('createPost')->andReturn([
            'data' => [
                'id' => $this->faker->randomDigitNotNull,
            ],
        ]);

        ImportRockTheVoteRecord::dispatch($this->faker->rockTheVoteReportRow(), $this->faker->randomDigitNotNull);
    }

    /**
     * Test that post is not created or updated if it exists with completed status.
     *
     * @return void
     */
    public function testDoesNotCreateOrUpdatePostIfCompletedPostFound()
    {
        $this->mockGetNorthstarUser();
        $this->northstarMock->shouldNotReceive('createUser');
        $this->northstarMock->shouldNotReceive('sendPasswordReset');
        $this->rogueMock->shouldReceive('getPost')->andReturn([
            'data' => [
                0 => [
                    'id' => $this->faker->randomDigitNotNull,
                    'status' => 'registration_complete',
                ],
            ],
        ]);
        $this->rogueMock->shouldNotReceive('createPost');
        $this->rogueMock->shouldNotReceive('updatePost');

        ImportRockTheVoteRecord::dispatch($this->faker->rockTheVoteReportRow(), $this->faker->randomDigitNotNull);
    }

    /**
     * Test that user is updated if their voter registration status should change.
     *
     * @return void
     */
    public function testUpdatesUserIfShouldChangeStatus()
    {
        $userId = $this->faker->northstar_id;
        $postId = $this->faker->randomDigitNotNull;

        $this->mockGetNorthstarUser([
            'id' => $userId,
            'voter_registration_status' => 'uncertain',
        ]);

        $this->northstarMock->shouldNotReceive('createUser');

        $this->rogueMock->shouldReceive('getPost')->andReturn([
            'data' => [
                0 => [
                    'id' => $postId,
                    'status' => 'uncertain',
                ],
            ],
        ]);

        $this->rogueMock->shouldNotReceive('createPost');

        $this->northstarMock->shouldReceive('updateUser')->with($userId, [
            'voter_registration_status' => 'registration_complete',
        ]);

        $this->rogueMock->shouldReceive('updatePost')->with($postId, [
            'status' => 'register-OVR',
        ]);

        ImportRockTheVoteRecord::dispatch($this->faker->rockTheVoteReportRow([
            'Status' => 'Complete',
            'Finish with State' => 'Yes',
        ]), $this->faker->randomDigitNotNull);
    }

    /**
     * Test that user is not updated if their voter registration status should not change.
     *
     * @return void
     */
    public function testDoesNotUpdatesUserIfShouldNotChangeStatus()
    {
        $user = (object) [
            'id' => $this->faker->northstar_id,
            'voter_registration_status' => 'registration_complete',
        ];

        $job = new ImportRockTheVoteRecord($this->faker->rockTheVoteReportRow([
            'Status' => 'Step 1',
            'Finish with State' => 'No',
        ]), $this->faker->randomDigitNotNull);

        $params = $job->getParameters();

        $this->northstarMock->shouldNotReceive('updateUser');

        $job->updateUserIfChanged($user);
    }

    /**
     * Test that status should update when update value has higher priority than the current.
     *
     * @return void
     */
    public function testShouldUpdateStatus()
    {
        $priority = [
            'uncertain',
            'ineligible',
            'unregistered',
            'confirmed',
            'register-OVR',
            'register-form',
            'registration_complete',
        ];

        for ($i = 0; $i < count($priority); $i++) {
            $firstValue = $priority[$i];

            for ($j = 0; $j < count($priority); $j++) {
                $secondValue = $priority[$j];

                if ($j > $i) {
                    $this->assertTrue(ImportRockTheVoteRecord::shouldUpdateStatus($firstValue, $priority[$j]));
                } else {
                    $this->assertFalse(ImportRockTheVoteRecord::shouldUpdateStatus($firstValue, $priority[$j]));
                }
            }
        }
    }
}
