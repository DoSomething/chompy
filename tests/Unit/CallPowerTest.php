<?php

namespace Tests\Unit;

use Exception;
use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Chompy\Jobs\CreateCallPowerPostInRogue;
use DoSomething\Gateway\Resources\NorthstarUser;

class CallPowerTest extends TestCase
{
    /**
     * Test that a post with a completed status successfully sends to Rogue.
     *
     * @return void
     */
    public function testCompletedCallStatus()
    {
        // Fake the job being dispatched.
        Bus::fake();

        // Create a post with completed as the status.
        $response = $this->withChompyApiKey()->json('POST', 'api/v1/callpower/call', [
            'mobile' => '1234567891',
            'callpower_campaign_id' => '1',
            'status' => 'completed',
            'call_timestamp' => '2017-11-09 06:34:01.185035',
            'call_duration' => 50,
            'campaign_target_name' => 'Mickey Mouse',
            'campaign_target_title' => 'Representative',
            'campaign_target_district' => 'FL-7',
            'callpower_campaign_name' => 'Test',
            'number_dialed_into' => '+12028519273',
        ]);

        $response->assertSuccessful();

        Bus::assertDispatched(CreateCallPowerPostInRogue::class);
    }

    /**
     * Test that a post with a busy status successfully sends to Rogue.
     *
     * @return void
     */
    public function testBusyCallStatus()
    {
        // Fake the job being dispatched.
        Bus::fake();

        // Create a post with completed as the status.
        $response = $this->withChompyApiKey()->json('POST', 'api/v1/callpower/call', [
            'mobile' => '1234567891',
            'callpower_campaign_id' => '1',
            'status' => 'busy',
            'call_timestamp' => '2017-11-09 06:34:01.185035',
            'call_duration' => 50,
            'campaign_target_name' => 'Mickey Mouse',
            'campaign_target_title' => 'Representative',
            'campaign_target_district' => 'FL-7',
            'callpower_campaign_name' => 'Test',
            'number_dialed_into' => '+12028519273',
        ]);

        $response->assertSuccessful();

        Bus::assertDispatched(CreateCallPowerPostInRogue::class);
    }

    /**
     * Test that a post with an invalid CallPower campaign id is not made.
     *
     * @return void
     */
    public function testFailedPostWithInvalidCallPowerCampaignId()
    {
        // Mock the Northstar call.
        $this->mockGetNorthstarUser();

        // If the provided Action ID doesn't exist in Rogue, this method throws:
        $this->rogueMock->shouldReceive('getActionIdFromCallPowerCampaignId')->andThrow(new Exception(500));

        // Since we don't have an action in Rogue, we shouldn't make a post:
        $this->rogueMock->shouldNotReceive('createPost');

        $this->expectException(Exception::class);

        CreateCallPowerPostInRogue::dispatch([
            'mobile' => '1234567891',
            'callpower_campaign_id' => 1,
            'status' => 'busy',
            'call_timestamp' => '2017-11-09 06:34:01.185035',
            'call_duration' => 50,
            'campaign_target_name' => 'Mickey Mouse',
            'campaign_target_title' => 'Representative',
            'campaign_target_district' => 'FL-7',
            'callpower_campaign_name' => 'Test',
            'number_dialed_into' => '+12028519273',
        ]);
    }

    /**
     * Test a failed record if the post is not able to be created.
     *
     * @return void
     */
    public function testFailedPostCreationInRogue()
    {
        // Mock the Northstar call.
        $this->mockGetNorthstarUser();

        // Mock the call to Rogue to get the action id.
        $this->rogueMock->shouldReceive('getActionIdFromCallPowerCampaignId');

        // Mock a failed call to Rogue when a post is not created.
        $this->rogueMock->shouldReceive('createPost')->andThrow(new Exception(500));

        $this->expectException(Exception::class);

        CreateCallPowerPostInRogue::dispatch([
            'mobile' => '1234567891',
            'callpower_campaign_id' => 1,
            'status' => 'busy',
            'call_timestamp' => '2017-11-09 06:34:01.185035',
            'call_duration' => 50,
            'campaign_target_name' => 'Mickey Mouse',
            'campaign_target_title' => 'Representative',
            'campaign_target_district' => 'FL-7',
            'callpower_campaign_name' => 'Test',
            'number_dialed_into' => '+12028519273',
        ]);
    }

    /**
     * Test a failed record if the user is not able to be created.
     *
     * @return void
     */
    public function testFailedUserCreationInNorthstar()
    {
        // Mock a failed Northstar response.
        $this->northstarMock->shouldReceive('getUser')->andReturn(null);
        $this->northstarMock->shouldReceive('createUser')->andThrow(new Exception(500));

        // Since we don't have a user, these methods should not be hit.
        $this->rogueMock->shouldNotReceive('getActionIdFromCallPowerCampaignId');
        $this->rogueMock->shouldNotReceive('createPost');

        $this->expectException(Exception::class);

        CreateCallPowerPostInRogue::dispatch([
            'mobile' => '1234567891',
            'callpower_campaign_id' => 1,
            'status' => 'busy',
            'call_timestamp' => '2017-11-09 06:34:01.185035',
            'call_duration' => 50,
            'campaign_target_name' => 'Mickey Mouse',
            'campaign_target_title' => 'Representative',
            'campaign_target_district' => 'FL-7',
            'callpower_campaign_name' => 'Test',
            'number_dialed_into' => '+12028519273',
        ]);
    }
}
