<?php

namespace Tests;

use Mockery;
use Carbon\Carbon;
use Chompy\Services\Rogue;
use DoSomething\Gateway\Northstar;
use Illuminate\Support\Facades\Storage;

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

        // Northstar Mock
        $this->northstarMock = $this->mock(Northstar::class);
        $this->northstarMock->shouldReceive('asClient')->andReturnSelf();
        $this->northstarMock->shouldReceive('refreshIfExpired')->andReturnSelf();

        // Rogue Mock
        $this->rogueMock = $this->mock(Rogue::class);
    }

    /**
     * Mock Container dependencies.
     *
     * @param string $class - Class to be mocked.
     *
     * @return \Mockery\MockInterface
     */
    public function mock($class)
    {
        $mock = Mockery::mock($class);
        $this->app->instance($class, $mock);

        return $mock;
    }

    /**
     * Mock the getUser Northstar Call.
     *
     * @return user
     */
    public function mockGetNorthstarUser()
    {
        $this->northstarMock->shouldReceive('getUser')->andReturnUsing(function ($type, $id) {
            return new NorthstarUser([
                    'id' => $type === 'id' ? $id : $this->faker->northstar_id,
                    'first_name' => $this->faker->firstName,
                    'last_name' => $this->faker->lastName,
                    'birthdate' => $this->faker->date,
                    'email' => $this->faker->email,
                    'mobile' => $this->faker->phoneNumber,
                ]);
        });
    }
}
