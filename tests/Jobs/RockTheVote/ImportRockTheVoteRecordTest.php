<?php

namespace Tests\Jobs\RockTheVote;

use Tests\TestCase;
use Chompy\RockTheVoteRecord;
use Chompy\Models\ImportFile;
use Chompy\Models\RockTheVoteLog;
use Chompy\Jobs\ImportRockTheVoteRecord;
use DoSomething\Gateway\Resources\NorthstarUser;

class ImportRockTheVoteRecordTest extends TestCase
{
    /**
     * Test that user and post are created if user not found.
     *
     * @return void
     */
    public function testCreatesUserIfUserNotFound()
    {
        $userId = $this->faker->northstar_id;
        $row = $this->faker->rockTheVoteReportRow();
        $importFile = factory(ImportFile::class)->create();

        $this->northstarMock->shouldReceive('getUser')->andReturn(null);
        $this->mockCreateNorthstarUser(['id' => $userId]);
        $this->northstarMock->shouldReceive('sendPasswordReset');
        $this->rogueMock->shouldReceive('getPosts')->andReturn(null);
        $this->rogueMock->shouldReceive('createPost')->andReturn([
            'data' => [
                'id' => $this->faker->randomDigitNotNull,
            ],
        ]);

        ImportRockTheVoteRecord::dispatch($row, $importFile);

        $this->assertDatabaseHas('rock_the_vote_logs', [
            'user_id' => $userId,
            'status' => $row['Status'],
            'started_registration' => $row['Started registration'],
            'import_file_id' => $importFile->id,
        ]);
    }

    /**
     * Test that user is not created if user is found.
     *
     * @return void
     */
    public function testDoesNotCreateUserIfUserFound()
    {
        $userId = $this->faker->northstar_id;
        $row = $this->faker->rockTheVoteReportRow([
            'Status' => 'Step 1',
        ]);
        $importFile = factory(ImportFile::class)->create();

        $this->mockGetNorthstarUser([
            'id' => $userId,
            'voter_registration_status' => 'step-1',
        ]);
        $this->northstarMock->shouldNotReceive('createUser');
        $this->northstarMock->shouldNotReceive('updateUser');
        $this->northstarMock->shouldNotReceive('sendPasswordReset');
        $this->rogueMock->shouldReceive('getPosts')->andReturn(null);
        $this->rogueMock->shouldReceive('createPost')->andReturn([
            'data' => [
                'id' => $this->faker->randomDigitNotNull,
            ],
        ]);

        ImportRockTheVoteRecord::dispatch($row, $importFile);

        $this->assertDatabaseHas('rock_the_vote_logs', [
            'user_id' => $userId,
            'status' => $row['Status'],
            'started_registration' => $row['Started registration'],
            'import_file_id' => $importFile->id,
        ]);
    }

    /**
     * Test that post is not created or updated if it exists with completed status.
     *
     * @return void
     */
    public function testDoesNotCreateOrUpdatePostIfCompletedPostFound()
    {
        $userId = $this->faker->northstar_id;
        $startedRegistration = $this->faker->rockTheVoteStartedRegistration();
        $row = $this->faker->rockTheVoteReportRow([
            'Started registration' => $startedRegistration,
        ]);
        $importFile = factory(ImportFile::class)->create();

        $this->mockGetNorthstarUser([
            'id' => $userId,
            'voter_registration_status' => 'registration_complete',
        ]);
        $this->northstarMock->shouldNotReceive('createUser');
        $this->northstarMock->shouldNotReceive('updateUser');
        $this->northstarMock->shouldNotReceive('sendPasswordReset');
        $this->rogueMock->shouldReceive('getPosts')->andReturn([
            'data' => [
                0 => $this->faker->rogueVoterRegPost(['northstar_id' => $userId, 'status' => 'register-form'], $startedRegistration),
            ],
        ]);
        $this->rogueMock->shouldNotReceive('createPost');
        $this->rogueMock->shouldNotReceive('updatePost');

        ImportRockTheVoteRecord::dispatch($row, $importFile);

        $this->assertDatabaseHas('rock_the_vote_logs', [
            'user_id' => $userId,
            'status' => $row['Status'],
            'started_registration' => $row['Started registration'],
            'import_file_id' => $importFile->id,
        ]);
    }

    /**
     * Test that user is updated if their voter registration status should change.
     *
     * @return void
     */
    public function testUpdatesUserIfShouldChangeStatus()
    {
        $userId = $this->faker->northstar_id;
        $startedRegistration = $this->faker->rockTheVoteStartedRegistration();
        $postId = $this->faker->randomDigitNotNull;
        $row = $this->faker->rockTheVoteReportRow([
            'Started registration' => $startedRegistration,
            'Status' => 'Complete',
            'Finish with State' => 'Yes',
        ]);
        $existingPost  = $this->faker->rogueVoterRegPost([
            'id' => $postId,
            'northstar_id' => $userId,
            'status' => 'step-1'
        ], $startedRegistration);
        $importFile = factory(ImportFile::class)->create();

        $this->mockGetNorthstarUser([
            'id' => $userId,
            'voter_registration_status' => 'step-1',
        ]);
        $this->northstarMock->shouldNotReceive('createUser');
        $this->rogueMock->shouldReceive('getPosts')->andReturn([
            'data' => [0 => $existingPost],
        ]);
        $this->rogueMock->shouldNotReceive('createPost');
        $this->northstarMock->shouldReceive('updateUser')->with($userId, [
            'voter_registration_status' => 'registration_complete',
        ]);
        $this->rogueMock->shouldReceive('updatePost')->with($postId, [
            'status' => 'register-OVR',
        ]);

        ImportRockTheVoteRecord::dispatch($row, $importFile);

        $this->assertDatabaseHas('rock_the_vote_logs', [
            'user_id' => $userId,
            'status' => $row['Status'],
            'started_registration' => $row['Started registration'],
            'import_file_id' => $importFile->id,
        ]);
    }

    /**
     * Test that user is not updated if their voter registration status should not change.
     *
     * @return void
     */
    public function testDoesNotUpdateUserIfShouldNotChangeStatus()
    {
        $userId = $this->faker->northstar_id;
        $startedRegistration = $this->faker->rockTheVoteStartedRegistration();
        $row = $this->faker->rockTheVoteReportRow([
            'Started registration' => $startedRegistration,
            'Status' => 'Step 1',
            'Finish with State' => 'No',
        ]);
        $existingPost = $this->faker->rogueVoterRegPost([
            'northstar_id' => $userId,
            'status' => 'register-OVR'
        ], $startedRegistration);
        $importFile = factory(ImportFile::class)->create();

        $this->mockGetNorthstarUser([
            'id' => $userId,
            'voter_registration_status' => 'registration_complete',
        ]);
        $this->northstarMock->shouldNotReceive('createUser');
        $this->northstarMock->shouldNotReceive('updateUser');
        $this->rogueMock->shouldReceive('getPosts')->andReturn([
            'data' => [0 => $existingPost],
        ]);
        $this->rogueMock->shouldNotReceive('createPost');
        $this->rogueMock->shouldNotReceive('updatePost');

        ImportRockTheVoteRecord::dispatch($row, $importFile);

        $this->assertDatabaseHas('rock_the_vote_logs', [
            'user_id' => $userId,
            'status' => $row['Status'],
            'started_registration' => $row['Started registration'],
            'import_file_id' => $importFile->id,
        ]);
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
        $importFile = factory(ImportFile::class)->create();

        $this->mockGetNorthstarUser([
            'id' => $userId,
        ]);

        $this->northstarMock->shouldNotReceive('updateUser');
        $this->rogueMock->shouldNotReceive('getPosts');
        $this->rogueMock->shouldNotReceive('createPost');
        $this->rogueMock->shouldNotReceive('updatePost');

        ImportRockTheVoteRecord::dispatch($row, $importFile);

        $this->assertDatabaseHas('import_files', [
            'id' => $importFile->id,
            'skip_count' => 1,
        ]);
    }

    /**
     * Test that an array is returned when a post is found.
     *
     * @return void
     */
    public function testGetPostWhenPostIsFound()
    {
        $userId = $this->faker->northstar_id;
        $startedRegistration = '1/23/19 20:22';
        $row = $this->faker->rockTheVoteReportRow([
            'Started registration' => $startedRegistration,
        ]);
        $record = new RockTheVoteRecord($row);
        $user = new NorthstarUser(['id' => $userId]);
        $post = $this->faker->rogueVoterRegPost([
            'northstar_id' => $userId,
            'status' => 'register-OVR',
        ], $startedRegistration);

        $this->rogueMock->shouldReceive('getPosts')
            ->with([
                'northstar_id' => $user->id,
                'action_id' => $record->postData['action_id'],
            ])
            ->andReturn(['data' => [0 => $post]]);

        $job = new ImportRockTheVoteRecord($row, factory(ImportFile::class)->create());

        $result = $job->getPost($user);

        $this->assertEquals($result, $post);
    }

    /**
     * Test that null is returned when a post is not found.
     *
     * @return void
     */
    public function testGetPostWhenPostIsNotFound()
    {
        $row = $this->faker->rockTheVoteReportRow();
        $record = new RockTheVoteRecord($row);
        $user = new NorthstarUser([
            'id' => $this->faker->northstar_id,
        ]);

        $this->rogueMock->shouldReceive('getPosts')
            ->with([
                'northstar_id' => $user->id,
                'action_id' => $record->postData['action_id'],
            ])
            ->andReturn(['data' => null]);

        $job = new ImportRockTheVoteRecord($row, factory(ImportFile::class)->create());

        $result = $job->getPost($user);

        $this->assertEquals($result, null);
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
