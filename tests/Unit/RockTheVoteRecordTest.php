<?php

namespace Tests\Unit;

use Tests\TestCase;
use Chompy\ImportType;
use Chompy\RockTheVoteRecord;

class RockTheVoteRecordTest extends TestCase
{
    public function getConfig()
    {
        return ImportType::getConfig(ImportType::$rockTheVote);
    }

    public function getExampleRow($data = [])
    {
        return array_merge([
            'Home address' => $this->faker->streetAddress,
            'Home unit' => $this->faker->randomDigit,
            'Home city' => $this->faker->city,
            'Home state' => $this->faker->state,
            'Home zip code' => $this->faker->postcode,
            'Email address' => $this->faker->email,
            'First name' => $this->faker->firstName,
            'Last name' => $this->faker->lastName,
            'Phone' => $this->faker->phoneNumber,
            'Finish with State' => null,
            'Pre-Registered' => null,
            'Started registration' => null,
            'Status' => null,
            'Tracking Source' => null,
            'Opt-in to Partner email?' => null,
            'Opt-in to Partner SMS/robocall' => null,
        ], $data);
    }

    /**
     * Test that userData and postData arrays are parsed from record.
     *
     * @return void
     */
    public function testSetsUserDataAndPostData()
    {
        $exampleRow = $this->getExampleRow();
        $config = $this->getConfig();

        $record = new RockTheVoteRecord($exampleRow, $this->getConfig());

        $this->assertEquals($record->userData['addr_street1'], $exampleRow['Home address']);
        $this->assertEquals($record->userData['addr_street2'], $exampleRow['Home unit']);
        $this->assertEquals($record->userData['addr_city'], $exampleRow['Home city']);
        $this->assertEquals($record->userData['email'], $exampleRow['Email address']);
        $this->assertEquals($record->userData['first_name'], $exampleRow['First name']);
        $this->assertEquals($record->userData['id'], null);
        $this->assertEquals($record->userData['last_name'], $exampleRow['Last name']);
        $this->assertEquals($record->userData['mobile'], $exampleRow['Phone']);
        $this->assertEquals($record->userData['referrer_user_id'], null);
        $this->assertEquals($record->userData['source'], config('services.northstar.client_credentials.client_id'));
        $this->assertEquals($record->userData['source_detail'], $config['user']['source_detail']);

        $this->assertEquals($record->postData['action_id'], $config['post']['action_id']);
        $this->assertEquals($record->postData['source'], $config['post']['source']);
        $this->assertEquals($record->postData['source_details'], null);
        $this->assertEquals($record->postData['type'], $config['post']['type']);
        $this->assertEquals($record->postData['referrer_user_id'], null);
    }

    /**
     * Test that a user ID is parsed from a tracking source that contains a user property.
     *
     * @return void
     */
    public function testSetsUserIdIfExistsInTrackingSource()
    {
        $trackingSource = 'user:58007c1242a0646e3a8b46b8,campaignID:8017,campaignRunID:8022,source:email,source_details:newsletter_bdaytrigger';

        $record = new RockTheVoteRecord($this->getExampleRow([
            'Tracking Source' => $trackingSource,
        ]), $this->getConfig());

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

        $record = new RockTheVoteRecord($this->getExampleRow([
            'Tracking Source' => $trackingSource,
        ]), $this->getConfig());

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

        $record = new RockTheVoteRecord($this->getExampleRow([
            'Tracking Source' => $trackingSource,
        ]), $this->getConfig());

        $this->assertEquals($record->userData['id'], null);
        $this->assertEquals($record->userData['referrer_user_id'], '5552aa34469c64ec7d8b715b');
        $this->assertEquals($record->postData['referrer_user_id'], '5552aa34469c64ec7d8b715b');
    }
}
