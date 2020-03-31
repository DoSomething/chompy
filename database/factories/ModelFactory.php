<?php

use Faker\Generator;
use Chompy\Models\RockTheVoteLog;
use Chompy\Models\RockTheVoteReport;

$factory->define(RockTheVoteLog::class, function (Generator $faker) {
    return [
        'finish_with_state' => 'No',
        'import_file_id' => $this->faker->randomDigitNotNull,
        'pre_registered' => 'No',
        'started_registration' => '2020-02-22 19:16:32 -0500',
        'status' => 'Step 1',
        'tracking_source' => 'ads',
        'user_id' => $this->faker->northstar_id,
    ];
});

$factory->define(RockTheVoteReport::class, function (Generator $faker) {
    return [
        'id' => $this->faker->randomDigitNotNull,
        'status' => 'queued',
    ];
});
