<?php

namespace Tests\Http;

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
            'Home unit' => null,
            'Home city' => $this->faker->city,
            'Home state' => null,
            'Home zip code' => null,
            'Email address' => null,
            'First name' => null,
            'Last name' => null,
            'Phone' => null,
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
