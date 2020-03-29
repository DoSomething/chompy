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
     * Test that user is updated if record indicates their voter registration status should change.
     *
     * @return void
     */
    public function testUpdatesUserIfShouldChangeStatus()
    {
        $user = (object) [
            'id' => $this->faker->northstar_id,
            'voter_registration_status' => 'uncertain',
        ];

        $job = new ImportRockTheVoteRecord($this->faker->rockTheVoteReportRow([
            'Status' => 'Complete',
            'Finish with State' => 'Yes',
        ]), $this->faker->randomDigitNotNull);

        $params = $job->getParameters();

        $this->northstarMock->shouldReceive('updateUser')->with($user->id, [
            'voter_registration_status' => $params['userData']['voter_registration_status'],
        ]);

        $job->updateUserIfChanged($user);
    }
}
