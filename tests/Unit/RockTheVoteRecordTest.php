<?php

namespace Tests\Unit;

use Tests\TestCase;
use Chompy\ImportType;
use Chompy\RockTheVoteRecord;

class RockTheVoteRecordTest extends TestCase
{
    /**
     * Test that userData and postData arrays are parsed from record.
     *
     * @return void
     */
    public function testSetsUserDataAndPostData()
    {
        $exampleRow = $this->faker->rockTheVoteReportRow([
            'Phone' => $this->faker->phoneNumber,
            'Opt-in to Partner SMS/robocall' => 'Yes',
        ]);
        $config = ImportType::getConfig(ImportType::$rockTheVote);

        $record = new RockTheVoteRecord($exampleRow, $config);

        $this->assertEquals($record->userData['addr_street1'], $exampleRow['Home address']);
        $this->assertEquals($record->userData['addr_street2'], $exampleRow['Home unit']);
        $this->assertEquals($record->userData['addr_city'], $exampleRow['Home city']);
        $this->assertEquals($record->userData['email'], $exampleRow['Email address']);
        $this->assertEquals($record->userData['email_subscription_status'], true);
        $this->assertEquals($record->userData['email_subscription_topics'], explode(',', $config['user']['email_subscription_topics']));
        $this->assertEquals($record->userData['first_name'], $exampleRow['First name']);
        $this->assertEquals($record->userData['id'], null);
        $this->assertEquals($record->userData['last_name'], $exampleRow['Last name']);
        $this->assertEquals($record->userData['mobile'], $exampleRow['Phone']);
        $this->assertEquals($record->userData['referrer_user_id'], null);
        $this->assertEquals($record->userData['sms_status'], 'active');
        $this->assertEquals($record->userData['sms_subscription_topics'], explode(',', $config['user']['sms_subscription_topics']));
        $this->assertEquals($record->userData['source'], config('services.northstar.client_credentials.client_id'));
        $this->assertEquals($record->userData['source_detail'], $config['user']['source_detail']);

        $this->assertEquals($record->postData['action_id'], $config['post']['action_id']);
        $this->assertEquals($record->postData['details'], json_encode([
            'Tracking Source' => $exampleRow['Tracking Source'],
            'Started registration' => $exampleRow['Started registration'],
            'Finish with State' => $exampleRow['Finish with State'],
            'Status' => $exampleRow['Status'],
            'Pre-Registered' => $exampleRow['Pre-Registered'],
            'Home zip code' => $exampleRow['Home zip code'],
        ]));
        $this->assertEquals($record->postData['source'], $config['post']['source']);
        $this->assertEquals($record->postData['source_details'], null);
        $this->assertEquals($record->postData['type'], $config['post']['type']);
        $this->assertEquals($record->postData['referrer_user_id'], null);
    }

    /**
     * Test that user is not subscribed if they did not opt-in.
     *
     * @return void
     */
    public function testDidNotOptIn()
    {
        $exampleRow = $this->faker->rockTheVoteReportRow([
            'Phone' => $this->faker->phoneNumber,
            'Opt-in to Partner email?' => 'No',
            'Opt-in to Partner SMS/robocall' => 'No',
        ]);

        $record = new RockTheVoteRecord($exampleRow);

        $this->assertEquals($record->userData['email_subscription_status'], false);
        $this->assertEquals($record->userData['email_subscription_topics'], []);
        $this->assertEquals($record->userData['sms_status'], 'stop');
        $this->assertEquals($record->userData['sms_subscription_topics'], []);
    }

    /**
     * Test that user mobile is not set if invalid.
     *
     * @return void
     */
    public function testInvalidMobile()
    {
        $exampleRow = $this->faker->rockTheVoteReportRow([
            'Phone' => '000-000-0000',
            'Opt-in to Partner SMS/robocall' => 'Yes',
        ]);

        $record = new RockTheVoteRecord($exampleRow);

        $this->assertEquals($record->userData['mobile'], null);
        $this->assertFalse(isset($record->userData['sms_status']));
        $this->assertFalse(isset($record->userData['sms_subscription_topics']));
    }

    /**
     * Test that user is not subscribed to SMS if mobile not provided.
     *
     * @return void
     */
    public function testMissingMobile()
    {
        $exampleRow = $this->faker->rockTheVoteReportRow([
            'Phone' => '',
            'Opt-in to Partner SMS/robocall' => 'Yes',
        ]);

        $record = new RockTheVoteRecord($exampleRow);

        $this->assertEquals($record->userData['mobile'], null);
        $this->assertFalse(isset($record->userData['sms_status']));
        $this->assertFalse(isset($record->userData['sms_subscription_topics']));
    }

    /**
     * Test that a user ID is parsed from a tracking source that contains a user property.
     *
     * @return void
     */
    public function testSetsUserIdIfExistsInTrackingSource()
    {
        $trackingSource = 'user:58007c1242a0646e3a8b46b8,campaignID:8017,campaignRunID:8022,source:email,source_details:newsletter_bdaytrigger';

        $record = new RockTheVoteRecord($this->faker->rockTheVoteReportRow([
            'Tracking Source' => $trackingSource,
        ]));

        $this->assertEquals($record->userData['id'], '58007c1242a0646e3a8b46b8');
        $this->assertEquals($record->userData['referrer_user_id'], null);
        $this->assertEquals($record->postData['referrer_user_id'], null);
    }

    /**
     * Test that a user ID is not set if tracking source does not contain a user property.
     *
     * @return void
     */
    public function testSetsUserIdToNullIfNotExistsInTrackingSource()
    {
        $trackingSource = 'campaignID:8017,campaignRunID:8022,source:email,source_details:newsletter_bdaytrigger';

        $record = new RockTheVoteRecord($this->faker->rockTheVoteReportRow([
            'Tracking Source' => $trackingSource,
        ]));

        $this->assertEquals($record->userData['id'], null);
        $this->assertEquals($record->userData['referrer_user_id'], null);
        $this->assertEquals($record->postData['referrer_user_id'], null);
    }

    /**
     * Test that a user ID is not set if tracking source contains a referral property.
     *
     * @return void
     */
    public function testSetsUserIdToNullIfReferralTrackingSource()
    {
        $trackingSource = 'user:5552aa34469c64ec7d8b715b,campaignID:7059,campaignRunID:8128,source:web,source_details:onlinedrivereferral,referral=true';

        $record = new RockTheVoteRecord($this->faker->rockTheVoteReportRow([
            'Tracking Source' => $trackingSource,
        ]));

        $this->assertEquals($record->userData['id'], null);
        $this->assertEquals($record->userData['referrer_user_id'], '5552aa34469c64ec7d8b715b');
        $this->assertEquals($record->postData['referrer_user_id'], '5552aa34469c64ec7d8b715b');
    }

    /**
     * Test expected values per given Status and Finish with State column values.
     *
     * @return void
     */
    public function testUserVoterRegistrationStatusAndPostStatus()
    {
        // Step values
        $record = new RockTheVoteRecord($this->faker->rockTheVoteReportRow([
            'Status' => 'Step 1',
            'Finish with State' => 'No',
        ]));

        $this->assertEquals($record->userData['voter_registration_status'], 'step-1');
        $this->assertEquals($record->postData['status'], 'step-1');

        // Complete + Did not finish with state
        $record = new RockTheVoteRecord($this->faker->rockTheVoteReportRow([
            'Status' => 'Complete',
            'Finish with State' => 'No',
        ]));

        $this->assertEquals($record->userData['voter_registration_status'], 'registration_complete');
        $this->assertEquals($record->postData['status'], 'register-form');

        // Complete + Finished with state
        $record = new RockTheVoteRecord($this->faker->rockTheVoteReportRow([
            'Status' => 'Complete',
            'Finish with State' => 'Yes',
        ]));

        $this->assertEquals($record->userData['voter_registration_status'], 'registration_complete');
        $this->assertEquals($record->postData['status'], 'register-OVR');

        // Rejected
        $record = new RockTheVoteRecord($this->faker->rockTheVoteReportRow([
            'Status' => 'Rejected',
            'Finish with State' => 'No',
        ]));

        $this->assertEquals($record->userData['voter_registration_status'], 'rejected');
        $this->assertEquals($record->postData['status'], 'rejected');

        // Under 18
        $record = new RockTheVoteRecord($this->faker->rockTheVoteReportRow([
            'Status' => 'Under 18',
            'Finish with State' => 'No',
        ]));

        $this->assertEquals($record->userData['voter_registration_status'], 'under-18');
        $this->assertEquals($record->postData['status'], 'under-18');
    }

    /**
     * Test expected value for getPostDetails result.
     *
     * @return void
     */
    public function testGetPostDetails()
    {
        $row = $this->faker->rockTheVoteReportRow();
        $record = new RockTheVoteRecord($row);

        $result = $record->getPostDetails();

        foreach (config('import.rock_the_vote.post.details') as $key) {
            $this->assertEquals($result[$key], $row[$key]);
        }
    }
}
