<?php

namespace Tests\Jobs\RockTheVote;

use Tests\TestCase;
use Chompy\Models\RockTheVoteLog;
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
        $this->mockGetNorthstarUser([
            'voter_registration_status' => 'registration_complete',
        ]);
        $this->northstarMock->shouldNotReceive('createUser');
        $this->northstarMock->shouldNotReceive('updateUser');
        $this->rogueMock->shouldReceive('getPost')->andReturn([
            'data' => [
                0 => [
                    'id' => $this->faker->randomDigitNotNull,
                    'status' => 'register-OVR',
                ],
            ],
        ]);
        $this->rogueMock->shouldNotReceive('createPost');
        $this->rogueMock->shouldNotReceive('updatePost');

        ImportRockTheVoteRecord::dispatch($this->faker->rockTheVoteReportRow([
            'Status' => 'Step 1',
            'Finish with State' => 'No',
        ]), $this->faker->randomDigitNotNull);
    }

    /**
     * Test that record is not imported if its log already exists.
     */
    public function testDoesNotProcessRecordIfLogExists()
    {
        $row = $this->faker->rockTheVoteReportRow();
        $userId = $this->faker->northstar_id;
        $log = factory(RockTheVoteLog::class)->create([
            'user_id' => $userId,
            'started_registration' => $row['Started registration'],
            'status' => $row['Status'],
        ]);

        $this->mockGetNorthstarUser([
            'id' => $userId,
        ]);

        $this->northstarMock->shouldNotReceive('updateUser');
        $this->rogueMock->shouldNotReceive('getPost');
        $this->rogueMock->shouldNotReceive('createPost');
        $this->rogueMock->shouldNotReceive('updatePost');

        ImportRockTheVoteRecord::dispatch($row, $this->faker->randomDigitNotNull);
    }

    /**
     * Test that status should update when update value has higher priority than the current.
     *
     * @return void
     */
    public function testShouldUpdateStatus()
    {
        $statusHierarchy = [
            'uncertain',
            'ineligible',
            'unregistered',
            'confirmed',
            'register-OVR',
            'register-form',
            'registration_complete',
        ];

        for ($i = 0; $i < count($statusHierarchy); $i++) {
            $firstValue = $statusHierarchy[$i];

            for ($j = 0; $j < count($statusHierarchy); $j++) {
                $secondValue = $statusHierarchy[$j];

                if ($j > $i) {
                    $this->assertTrue(ImportRockTheVoteRecord::shouldUpdateStatus($firstValue, $statusHierarchy[$j]));
                } else {
                    $this->assertFalse(ImportRockTheVoteRecord::shouldUpdateStatus($firstValue, $statusHierarchy[$j]));
                }
            }
        }
    }
}
