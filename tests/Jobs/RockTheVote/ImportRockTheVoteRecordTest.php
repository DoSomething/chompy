<?php

namespace Tests\Jobs\RockTheVote;

use Tests\TestCase;
use Chompy\SmsStatus;
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
            'data' => $this->faker->rogueVoterRegPost(),
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
            'data' => $this->faker->rogueVoterRegPost(),
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
        $startedRegistration = $this->faker->daysAgoInRockTheVoteFormat();
        $row = $this->faker->rockTheVoteReportRow([
            'Started registration' => $startedRegistration,
        ]);
        $existingCompletedPost = $this->faker->rogueVoterRegPost([
            'northstar_id' => $userId,
            'status' => 'register-form',
        ], $startedRegistration);
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
                0 => $existingCompletedPost,
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
        $startedRegistration = $this->faker->daysAgoInRockTheVoteFormat();
        $postId = $this->faker->randomDigitNotNull;
        $row = $this->faker->rockTheVoteReportRow([
            'Phone' => $this->faker->phoneNumber,
            'Started registration' => $startedRegistration,
            'Status' => 'Complete',
            'Finish with State' => 'Yes',
        ]);
        $existingInProgressPost = $this->faker->rogueVoterRegPost([
            'id' => $postId,
            'northstar_id' => $userId,
            'status' => 'step-1',
        ], $startedRegistration);
        $importFile = factory(ImportFile::class)->create();

        $this->mockGetNorthstarUser([
            'id' => $userId,
            'voter_registration_status' => 'step-1',
        ]);
        $this->northstarMock->shouldNotReceive('createUser');
        $this->rogueMock->shouldReceive('getPosts')->andReturn([
            'data' => [0 => $existingInProgressPost],
        ]);
        $this->rogueMock->shouldNotReceive('createPost');
        $this->northstarMock->shouldReceive('updateUser')->with($userId, [
            /**
             * Note: If the ROCK_THE_VOTE_UPDATE_USER_SMS_ENABLED config var is set to true, we'd
             * additionally update the user mobile field here too.
             */
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
     * Test that user mobile is not updated when row has a phone, user does not have a mobile, and
     * update_user_sms_enabled config is false.
     *
     * @return void
     */
    public function testUserUpdatePayloadDoesNotContainMobileIfUpdateUserSmsConfigIsDisabled()
    {
        $user = new NorthstarUser([
            'id' => $this->faker->northstar_id,
            'voter_registration_status' => 'step-1',
        ]);
        $phoneNumber = $this->faker->phoneNumber;
        $row = $this->faker->rockTheVoteReportRow([
            'Opt-in to Partner SMS/robocall' => 'Yes',
            'Phone' =>  $phoneNumber,
            'Status' => 'Step 2',
        ]);
        $job = new ImportRockTheVoteRecord($row, factory(ImportFile::class)->create());

        $this->northstarMock->shouldReceive('updateUser')->with($user->id, [
            'voter_registration_status' => 'step-2',
        ]);

        $job->updateUserIfChanged($user);
    }

    /**
     * Test that user mobile is updated when row has a phone, user does not have a mobile, and
     * update_user_sms_enabled config is true.
     *
     * @return void
     */
    public function testUserUpdatePayloadContainsMobileIfUpdateUserSmsConfigIsEnabled()
    {
        \Config::set('import.rock_the_vote.update_user_sms_enabled', 'true');

        $user = new NorthstarUser([
            'id' => $this->faker->northstar_id,
            'voter_registration_status' => 'step-1',
        ]);
        $phoneNumber = $this->faker->phoneNumber;
        $row = $this->faker->rockTheVoteReportRow([
            'Opt-in to Partner SMS/robocall' => 'Yes',
            'Phone' =>  $phoneNumber,
            'Status' => 'Step 2',
        ]);
        $job = new ImportRockTheVoteRecord($row, factory(ImportFile::class)->create());

        $this->northstarMock->shouldReceive('updateUser')->with($user->id, [
            'mobile' => $phoneNumber,
            'sms_status' => SmsStatus::$active,
            'sms_subscription_topics' => ['voting'],
            'voter_registration_status' => 'step-2',
        ]);

        $job->updateUserIfChanged($user);

        \Config::set('import.rock_the_vote.update_user_sms_enabled', 'false');
    }

    /**
     * Test that user is not updated if their voter registration status should not change.
     *
     * @return void
     */
    public function testDoesNotUpdateUserIfShouldNotChangeStatus()
    {
        $userId = $this->faker->northstar_id;
        $startedRegistration = $this->faker->daysAgoInRockTheVoteFormat();
        $row = $this->faker->rockTheVoteReportRow([
            'Started registration' => $startedRegistration,
            'Status' => 'Step 1',
            'Finish with State' => 'No',
        ]);
        $olderExistingCompletedPost = $this->faker->rogueVoterRegPost([
            'northstar_id' => $userId,
            'status' => 'register-OVR',
        ], $this->faker->daysAgoInRockTheVoteFormat(1));
        $importFile = factory(ImportFile::class)->create();

        $this->mockGetNorthstarUser([
            'id' => $userId,
            'voter_registration_status' => 'registration_complete',
        ]);
        $this->northstarMock->shouldNotReceive('createUser');
        // We shouldn't update the user's status to the row status, which has lower priority.
        $this->northstarMock->shouldNotReceive('updateUser');
        $this->rogueMock->shouldReceive('getPosts')->andReturn([
            'data' => [0 => $olderExistingCompletedPost],
        ]);
        // But we do create a new post, since we don't have one for this row's Started registration.
        $this->rogueMock->shouldReceive('createPost');
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
     * Test that an array is returned when a post with same Started Registration is found.
     *
     * @return void
     */
    public function testGetPostWhenPostWithSameStartedRegistrationIsFound()
    {
        $userId = $this->faker->northstar_id;
        $startedRegistration = $this->faker->daysAgoInRockTheVoteFormat();
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
                'type' => config('import.rock_the_vote.post.type'),
            ])
            ->andReturn(['data' => [0 => $post]]);

        $job = new ImportRockTheVoteRecord($row, factory(ImportFile::class)->create());

        $result = $job->getPost($user);

        $this->assertEquals($result, $post);
    }

    /**
     * Test that an array is returned when a post with same Started Registration is found.
     *
     * @return void
     */
    public function testGetPostWhenPostWithSameStartedRegistrationIsNotFound()
    {
        $userId = $this->faker->northstar_id;
        $startedRegistration = $this->faker->daysAgoInRockTheVoteFormat();
        $row = $this->faker->rockTheVoteReportRow([
            'Started registration' => $startedRegistration,
        ]);
        $record = new RockTheVoteRecord($row);
        $user = new NorthstarUser(['id' => $userId]);
        $firstPost = $this->faker->rogueVoterRegPost([
            'northstar_id' => $userId,
            'status' => 'register-OVR',
        ], $this->faker->daysAgoInRockTheVoteFormat(2));
        $secondPost = $this->faker->rogueVoterRegPost([
            'northstar_id' => $userId,
            'status' => 'step-1',
        ], $this->faker->daysAgoInRockTheVoteFormat(4));

        $this->rogueMock->shouldReceive('getPosts')
            ->with([
                'northstar_id' => $user->id,
                'action_id' => $record->postData['action_id'],
                'type' => config('import.rock_the_vote.post.type'),
            ])
            ->andReturn(['data' => [0 => $firstPost, 1 => $secondPost]]);

        $job = new ImportRockTheVoteRecord($row, factory(ImportFile::class)->create());

        $result = $job->getPost($user);

        $this->assertEquals($result, null);
    }

    /**
     * Test that null is returned when a post is not found.
     *
     * @return void
     */
    public function testGetPostWhenNoPostsAreNotFound()
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
                'type' => config('import.rock_the_vote.post.type'),
            ])
            ->andReturn(['data' => null]);

        $job = new ImportRockTheVoteRecord($row, factory(ImportFile::class)->create());

        $result = $job->getPost($user);

        $this->assertEquals($result, null);
    }

    /*
     * Test that update SMS payload is empty if a mobile is not provided.
     *
     * @return void
     */
    public function testSmsSubscriptionPayloadIsEmptyIfMobileIsNull()
    {
        $row = $this->faker->rockTheVoteReportRow();
        $job = new ImportRockTheVoteRecord($row, factory(ImportFile::class)->create());

        $result = $job->getUserSmsSubscriptionUpdatePayload(new NorthstarUser([
            'id' => $this->faker->northstar_id,
        ]));

        $this->assertEquals([], $result);
    }

    /**
     * Test that update SMS payload is empty if we've already processed the registration's phone
     * number.
     *
     * @return void
     */
    public function testSmsSubscriptionPayloadIsEmptyIfAlreadyUpdatedSmsSubscription()
    {
        $user = new NorthstarUser([
            'id' => $this->faker->northstar_id,
        ]);
        $startedRegistration = $this->faker->daysAgoInRockTheVoteFormat();
        $row = $this->faker->rockTheVoteReportRow([
            'Phone' => $this->faker->phoneNumber,
            'Started registration' => $startedRegistration,
        ]);
        $log = factory(RockTheVoteLog::class)->create([
            'user_id' => $user->id,
            'started_registration' => $startedRegistration,
            'contains_phone' => true,
        ]);
        $job = new ImportRockTheVoteRecord($row, factory(ImportFile::class)->create());

        $result = $job->getUserSmsSubscriptionUpdatePayload($user);

        $this->assertEquals([], $result);
    }

    /**
     * Test that update SMS payload contains mobile if we haven't processed the registration's
     * phone number, and the user doesn't have a mobile set.
     *
     * @return void
     */
    public function testSmsSubscriptionPayloadContainsMobileIfHaveNotUpdatedSmsSubscription()
    {
        $user = new NorthstarUser([
            'id' => $this->faker->northstar_id,
        ]);
        $phoneNumber = $this->faker->phoneNumber;
        $row = $this->faker->rockTheVoteReportRow([
            'Phone' => $phoneNumber,
        ]);
        $job = new ImportRockTheVoteRecord($row, factory(ImportFile::class)->create());

        $result = $job->getUserSmsSubscriptionUpdatePayload($user);

        $this->assertEquals(['mobile' => $phoneNumber, 'sms_status' => SmsStatus::$stop], $result);
    }

    /**
     * Test that update SMS payload is empty if we haven't processed the registration's phone
     * number, but the user already has a mobile set.
     *
     * @return void
     */
    public function testSmsSubscriptionPayloadIsEmptyIfUserAlreadyHasMobile()
    {
        $user = new NorthstarUser([
            'id' => $this->faker->northstar_id,
            'mobile' => $this->faker->phoneNumber,
            'sms_status' => SmsStatus::$less,
        ]);
        $row = $this->faker->rockTheVoteReportRow([
            'Phone' => $this->faker->phoneNumber,
        ]);
        $job = new ImportRockTheVoteRecord($row, factory(ImportFile::class)->create());

        $result = $job->getUserSmsSubscriptionUpdatePayload($user);

        $this->assertEquals([], $result);
    }

    /**
     * ---------------------------
     * parseSmsStatusChangeForUser
     * ---------------------------
     */

    /**
     * Return mock user and row for a parseSmsStatusChange test.
     *
     * @param string $currentSmsStatus
     * @param bool @rtvSmsOptIn
     * @return obj
     */
    public function getMocksForParseSmsStatusChangeTest($currentSmsStatus, bool $rtvSmsOptIn)
    {
        $user = new NorthstarUser([
            'id' => $this->faker->northstar_id,
            'mobile' => $this->faker->phoneNumber,
            'sms_status' => $currentSmsStatus,
        ]);
        $row = $this->faker->rockTheVoteReportRow([
            RockTheVoteRecord::$mobileFieldName => $this->faker->phoneNumber,
            RockTheVoteRecord::$smsOptInFieldName => $rtvSmsOptIn ? 'Yes' : 'No',
        ]);

        return (object) ['user' => $user, 'row' => $row];
    }

    /**
     * Tests SMS status is updated to active if user with null value opts-in.
     *
     * @return void
     */
    public function testParseSmsStatusChangeIfUserHasNullSmsStatusAndOptsIn()
    {
        $mocks = $this->getMocksForParseSmsStatusChangeTest(null, true);
        $job = new ImportRockTheVoteRecord($mocks->row, factory(ImportFile::class)->create());

        $result = $job->parseSmsStatusChangeForUser($mocks->user);

        $this->assertEquals(['sms_status' => SmsStatus::$active], $result);
    }

    /**
     * Tests SMS status is updated to stop if user with null value opts-out.
     *
     * @return void
     */
    public function testParseSmsStatusChangeIfUserHasNullSmsStatusAndOptsOut()
    {
        $mocks = $this->getMocksForParseSmsStatusChangeTest(null, false);
        $job = new ImportRockTheVoteRecord($mocks->row, factory(ImportFile::class)->create());

        $result = $job->parseSmsStatusChangeForUser($mocks->user);

        $this->assertEquals(['sms_status' => SmsStatus::$stop], $result);
    }

    /**
     * Tests that update is empty if user with active value opts-in.
     *
     * @return void
     */
    public function testParseSmsStatusChangeIfUserHasActiveSmsStatusAndOptsIn()
    {
        $mocks = $this->getMocksForParseSmsStatusChangeTest(SmsStatus::$active, true);
        $job = new ImportRockTheVoteRecord($mocks->row, factory(ImportFile::class)->create());

        $result = $job->parseSmsStatusChangeForUser($mocks->user);

        $this->assertEquals([], $result);
    }

    /**
     * Tests that update is empty if user with active value opts-out.
     *
     * @return void
     */
    public function testParseSmsStatusChangeIfUserHasActiveSmsStatusAndOptsOut()
    {
        $mocks = $this->getMocksForParseSmsStatusChangeTest(SmsStatus::$active, false);
        $job = new ImportRockTheVoteRecord($mocks->row, factory(ImportFile::class)->create());

        $result = $job->parseSmsStatusChangeForUser($mocks->user);

        $this->assertEquals([], $result);
    }

    /**
     * Test that SMS status is updated to active if user with less value opts-in
     *
     * @return void
     */
    public function testParseSmsStatusChangeIfUserHasLessSmsStatusAndOptsIn()
    {
        $mocks = $this->getMocksForParseSmsStatusChangeTest(SmsStatus::$less, true);
        $job = new ImportRockTheVoteRecord($mocks->row, factory(ImportFile::class)->create());

        $result = $job->parseSmsStatusChangeForUser($mocks->user);

        $this->assertEquals(['sms_status' => SmsStatus::$active], $result);
    }

    /**
     * Test that update is empty if user with less value opts-out.
     *
     * @return void
     */
    public function testParseSmsStatusChangeIfUserHasLessSmsStatusAndOptsOut()
    {
        $mocks = $this->getMocksForParseSmsStatusChangeTest(SmsStatus::$less, false);
        $job = new ImportRockTheVoteRecord($mocks->row, factory(ImportFile::class)->create());

        $result = $job->parseSmsStatusChangeForUser($mocks->user);

        $this->assertEquals([], $result);
    }

    /**
     * Test that SMS status is updated to active if user with pending value opts-in.
     *
     * @return void
     */
    public function testParseSmsStatusChangeIfUserHasPendingSmsStatusAndOptsIn()
    {
        $mocks = $this->getMocksForParseSmsStatusChangeTest(SmsStatus::$pending, true);
        $job = new ImportRockTheVoteRecord($mocks->row, factory(ImportFile::class)->create());

        $result = $job->parseSmsStatusChangeForUser($mocks->user);

        $this->assertEquals(['sms_status' => SmsStatus::$active], $result);
    }

    /**
     * Test that update is empty if user with pending value opts-out.
     *
     * @return void
     */
    public function testParseSmsStatusChangeIfUserHasPendingSmsStatusAndOptsOut()
    {
        $mocks = $this->getMocksForParseSmsStatusChangeTest(SmsStatus::$pending, false);
        $job = new ImportRockTheVoteRecord($mocks->row, factory(ImportFile::class)->create());

        $result = $job->parseSmsStatusChangeForUser($mocks->user);

        $this->assertEquals([], $result);
    }

    /**
     * Test that SMS status is updated to active if user with stop value opts-in.
     *
     * @return void
     */
    public function testParseSmsStatusChangeIfUserHasStopSmsStatusAndOptsIn()
    {
        $mocks = $this->getMocksForParseSmsStatusChangeTest(SmsStatus::$stop, true);
        $job = new ImportRockTheVoteRecord($mocks->row, factory(ImportFile::class)->create());

        $result = $job->parseSmsStatusChangeForUser($mocks->user);

        $this->assertEquals(['sms_status' => SmsStatus::$active], $result);
    }

    /**
     * Test that update is empty if user with stop value opts-out.
     *
     * @return void
     */
    public function testParseSmsStatusChangeIfUserHasStopSmsStatusAndOptsOut()
    {
        $mocks = $this->getMocksForParseSmsStatusChangeTest(SmsStatus::$stop, false);
        $job = new ImportRockTheVoteRecord($mocks->row, factory(ImportFile::class)->create());

        $result = $job->parseSmsStatusChangeForUser($mocks->user);

        $this->assertEquals([], $result);
    }

    /**
     * Tests SMS status is updated to active if user with undeliverable value opts-in to RTV SMS.
     *
     * @return void
     */
    public function testParseSmsStatusChangeIfUserHasUndeliverableSmsStatusAndOptsIn()
    {
        $mocks = $this->getMocksForParseSmsStatusChangeTest(SmsStatus::$undeliverable, true);
        $job = new ImportRockTheVoteRecord($mocks->row, factory(ImportFile::class)->create());

        $result = $job->parseSmsStatusChangeForUser($mocks->user);

        $this->assertEquals(['sms_status' => SmsStatus::$active], $result);
    }

    /**
     * Tests SMS status is updated to stop if user with undeliverable value opts-in to RTV SMS.
     *
     * @return void
     */
    public function testParseSmsStatusChangeIfUserHasUndeliverableSmsStatusAndOptsOut()
    {
        $mocks = $this->getMocksForParseSmsStatusChangeTest(SmsStatus::$undeliverable, false);
        $job = new ImportRockTheVoteRecord($mocks->row, factory(ImportFile::class)->create());

        $result = $job->parseSmsStatusChangeForUser($mocks->user);

        $this->assertEquals(['sms_status' => SmsStatus::$stop], $result);
    }

    /**
     * ---------------------------------------
     * parseSmsSubscriptionTopicsChangeForUser
     * ---------------------------------------
     */

    /**
     * Tests that import topic is removed from SMS topics if user opts-out of RTV SMS.
     *
     * @return void
     */
    public function testParseSubscriptionTopicsChangeIfUserOptsOut()
    {
        $user = new NorthstarUser([
            'id' => $this->faker->northstar_id,
            'mobile' => $this->faker->phoneNumber,
            'sms_status' => SmsStatus::$active,
            'sms_subscription_topics' => ['general', 'voting', 'pizza'],
        ]);
        $row = $this->faker->rockTheVoteReportRow([
            RockTheVoteRecord::$mobileFieldName => $this->faker->phoneNumber,
            RockTheVoteRecord::$smsOptInFieldName => 'No',
        ]);
        $job = new ImportRockTheVoteRecord($row, factory(ImportFile::class)->create());

        $result = $job->parseSmsSubscriptionTopicsChangeForUser($user);

        $this->assertEquals(['sms_subscription_topics' => ['general', 'pizza']], $result);
    }

    /**
     * Tests for empty payload if user does not have topics set and opts-out of RTV SMS.
     *
     * @return void
     */
    public function testParseSubscriptionTopicsChangeIfUserDoesNotHaveTopicsAndOptsOut()
    {
        $user = new NorthstarUser([
            'id' => $this->faker->northstar_id,
            'mobile' => $this->faker->phoneNumber,
            'sms_status' => SmsStatus::$active,
            'sms_subscription_topics' => null,
        ]);
        $row = $this->faker->rockTheVoteReportRow([
            RockTheVoteRecord::$mobileFieldName => $this->faker->phoneNumber,
            RockTheVoteRecord::$smsOptInFieldName => 'No',
        ]);
        $job = new ImportRockTheVoteRecord($row, factory(ImportFile::class)->create());

        $result = $job->parseSmsSubscriptionTopicsChangeForUser($user);

        $this->assertEquals([], $result);
    }

    /**
     * Tests that import topic is added from SMS topics if user opts-in to RTV SMS.
     *
     * @return void
     */
    public function testParseSubscriptionTopicsChangeIfUserHasTopicsAndOptsIn()
    {
        $user = new NorthstarUser([
            'id' => $this->faker->northstar_id,
            'mobile' => $this->faker->phoneNumber,
            'sms_status' => SmsStatus::$active,
            'sms_subscription_topics' => ['general', 'sushi', 'batman'],
        ]);
        $row = $this->faker->rockTheVoteReportRow([
            RockTheVoteRecord::$mobileFieldName => $this->faker->phoneNumber,
            RockTheVoteRecord::$smsOptInFieldName => 'Yes',
        ]);
        $job = new ImportRockTheVoteRecord($row, factory(ImportFile::class)->create());

        $result = $job->parseSmsSubscriptionTopicsChangeForUser($user);

        $this->assertEquals(['sms_subscription_topics' => ['general', 'sushi', 'batman', 'voting']], $result);
    }

    /**
     * ------------------
     * shouldUpdateStatus
     * ------------------
     */

    /**
     * Test that status should update when update value has higher priority than the current.
     *
     * @return void
     */
    public function testShouldUpdateStatus()
    {
        $statusHierarchy = config('import.rock_the_vote.status_hierarchy');

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
