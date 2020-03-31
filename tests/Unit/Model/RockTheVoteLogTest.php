<?php

namespace Tests\Http;

use Tests\TestCase;
use Chompy\RockTheVoteRecord;
use Chompy\Models\RockTheVoteLog;
use DoSomething\Gateway\Resources\NorthstarUser;

class RockTheVoteLogTest extends TestCase
{
    /**
     * Test that getByRecord returns a RockTheVoteLog when record and user found.
     *
     * @return void
     */
    public function testGetByRecordWhenFound()
    {
        $user = new NorthstarUser(['id' => $this->faker->northstar_id]);
        $row = $this->faker->rockTheVoteReportRow();
        $record = new RockTheVoteRecord($row);

        $log = factory(RockTheVoteLog::class)->create([
            'user_id' => $user->id,
            'started_registration' => $row['Started registration'],
            'status' => $row['Status'],
        ]);

        $result = RockTheVoteLog::getByRecord($record, $user);

        $this->assertEquals($log->user_id, $user->id);
        $this->assertEquals($log->started_registration, $row['Started registration']);
        $this->assertEquals($log->status, $row['Status']);
    }

    /**
     * Test that getByRecord returns null when record and user not found.
     *
     * @return void
     */
    public function testGetByRecordWhenNotFound()
    {
        $user = new NorthstarUser(['id' => $this->faker->northstar_id]);
        $record = new RockTheVoteRecord($this->faker->rockTheVoteReportRow());

        $result = RockTheVoteLog::getByRecord($record, $user);

        $this->assertEquals($result, null);
    }
}
