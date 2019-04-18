<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * The Faker generator, for creating test data.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $this->app = require __DIR__.'/../bootstrap/app.php';

        $this->app->make(Kernel::class)->bootstrap();

        return $this->app;
    }
}
