<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Chompy\RockTheVoteRecord;
use Chompy\Models\ImportFile;
use Chompy\Models\RockTheVoteLog;
use DoSomething\Gateway\Resources\NorthstarUser;

class RockTheVoteLogTest extends TestCase
{
    /**
     * Test that createFromRecord returns a RockTheVoteLog and increments given ImportFile.
     *
     * @return void
     */
    public function testCreateFromRecordThatContainsPhone()
    {
        $user = new NorthstarUser(['id' => $this->faker->northstar_id]);
        $row = $this->faker->rockTheVoteReportRow([]);
        $importFile = factory(ImportFile::class)->create([
            'import_count' => 5,
        ]);

        $log = RockTheVoteLog::createFromRecord(new RockTheVoteRecord($row), $user, $importFile);

        $this->assertEquals($log->contains_phone, true);
        $this->assertEquals($log->finish_with_state, $row['Finish with State']);
        $this->assertEquals($log->import_file_id, $importFile->id);
        $this->assertEquals($log->pre_registered, $row['Pre-Registered']);
        $this->assertEquals($log->started_registration, $row['Started registration']);
        $this->assertEquals($log->status, $row['Status']);
        $this->assertEquals($log->tracking_source, $row['Tracking Source']);
        $this->assertEquals($log->user_id, $user->id);

        $this->assertDatabaseHas('import_files', [
            'id' => $importFile->id,
            'import_count' => 6,
        ]);
    }

    /**
     * Test that false is saved for contains_phone if row does not contain phone.
     *
     * @return void
     */
    public function testCreateFromRecordThatDoesNotContainPhone()
    {
        $user = new NorthstarUser(['id' => $this->faker->northstar_id]);
        $row = $this->faker->rockTheVoteReportRow([
            'Phone' => null,
        ]);
        $importFile = factory(ImportFile::class)->create();

        $log = RockTheVoteLog::createFromRecord(new RockTheVoteRecord($row), $user, $importFile);

        $this->assertEquals($log->contains_phone, false);
    }

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
