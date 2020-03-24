<?php

namespace Tests\Http;

use Tests\TestCase;
use Chompy\ImportType;
use Chompy\RockTheVoteRecord;

class RockTheVoteRecordTest extends TestCase
{
    public function getExampleRow()
    {
        return [
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
        ];
    }

    /**
     * Test that a user ID is parsed from a tracking source that contains a user property.
     *
     * @return void
     */
    public function testSetsUserIdIfExistsInTrackingSource()
    {
        $record = new RockTheVoteRecord($this->getExampleRow(), ImportType::getConfig(ImportType::$rockTheVote));

        $record->setUserId('user:58007c1242a0646e3a8b46b8,campaignID:8017,campaignRunID:8022,source:email,source_details:newsletter_bdaytrigger');

        $this->assertEquals($record->userData['id'], '58007c1242a0646e3a8b46b8');
    }

    /**
     * Test that a user ID is not set if tracking source does not contain a user property.
     *
     * @return void
     */
    public function testSetsUserIdToNullIfNotExistsInTrackingSource()
    {
        $record = new RockTheVoteRecord($this->getExampleRow(), ImportType::getConfig(ImportType::$rockTheVote));

        $record->setUserId('campaignID:8017,campaignRunID:8022,source:email,source_details:newsletter_bdaytrigger');

        $this->assertEquals($record->userData['id'], null);
    }

    /**
     * Test that a user ID is not set if tracking source contains a referral property.
     *
     * @return void
     */
    public function testSetsUserIdToNullIfReferralTrackingSource()
    {
        $record = new RockTheVoteRecord($this->getExampleRow(), ImportType::getConfig(ImportType::$rockTheVote));

        $record->setUserId('user:5552aa34469c64ec7d8b715b,campaignID:7059,campaignRunID:8128,source:web,source_details:onlinedrivereferral,referral=true');

        $this->assertEquals($record->userData['id'], null);
    }
}
