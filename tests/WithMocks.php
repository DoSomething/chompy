<?php

namespace Tests;

use Carbon\Carbon;
use Chompy\Services\Rogue;
use Chompy\Services\RockTheVote;
use DoSomething\Gateway\Northstar;
use Illuminate\Support\Facades\Storage;
use DoSomething\Gateway\Resources\NorthstarUser;

trait WithMocks
{
    /**
     * The Faker instance for the request.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Configure mocks for the application.
     */
    public function configureMocks()
    {
        // Reset mocked time, if set.
        Carbon::setTestNow(null);

        // Fake the storage driver.
        Storage::fake('public');

        // Get a new Faker generator from Laravel.
        $this->faker = app(\Faker\Generator::class);
        $this->faker->addProvider(new \FakerNorthstarId($this->faker));
        $this->faker->addProvider(new \FakerRockTheVoteReportRow($this->faker));
        $this->faker->addProvider(new \FakerRogueVoterRegPost($this->faker));

        // Northstar Mock
        $this->northstarMock = $this->mock(Northstar::class);
        $this->northstarMock->shouldReceive('asClient')->andReturnSelf();
        $this->northstarMock->shouldReceive('refreshIfExpired')->andReturnSelf();

        // Rogue Mock
        $this->rogueMock = $this->mock(Rogue::class);

        // Rock The Vote Mock
        $this->rockTheVoteMock = $this->mock(RockTheVote::class);
    }

    /**
     * Mock the getUser Northstar call.
     *
     * @param array $data
     * @return NorthstarUser
     */
    public function mockGetNorthstarUser($data = [])
    {
        $this->northstarMock->shouldReceive('getUser')->andReturnUsing(function ($id) use (&$data) {
            return new NorthstarUser(array_merge([
                'id' => $id,
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
                'birthdate' => $this->faker->date,
                'email' => $this->faker->email,
                'mobile' => $this->faker->phoneNumber,
            ], $data));
        });
    }

    /**
     * Mock the createUser Northstar call.
     *
     * @param array $data
     * @return NorthstarUser
     */
    public function mockCreateNorthstarUser($data = [])
    {
        $this->northstarMock
            ->shouldReceive('createUser')
            ->andReturn(new NorthstarUser(array_merge([
                'id' => $this->faker->northstar_id,
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
                'birthdate' => $this->faker->date,
                'email' => $this->faker->email,
                'mobile' => $this->faker->phoneNumber,
            ], $data)));
    }

    /**
     * "Freeze" time so we can make assertions based on it.
     *
     * @param string $time
     * @return Carbon
     */
    public function mockTime($time = 'now')
    {
        Carbon::setTestNow((string) new Carbon($time));

        return Carbon::getTestNow();
    }
}
