<?php

use Carbon\Carbon;
use Faker\Generator;
use Chompy\ImportType;
use Chompy\Models\ImportFile;
use Chompy\Models\RockTheVoteLog;
use Chompy\Models\RockTheVoteReport;

$factory->define(ImportFile::class, function (Generator $faker) {
    return [
        'id' => $this->faker->randomDigitNotNull,
        'filepath' => $this->faker->imageUrl,
        'import_type' => ImportType::$rockTheVote,
        'row_count' => $this->faker->numberBetween(10, 1250),
    ];
});

$factory->define(RockTheVoteLog::class, function (Generator $faker) {
    return [
        'finish_with_state' => 'No',
        'import_file_id' => $this->faker->randomDigitNotNull,
        'pre_registered' => 'No',
        'started_registration' => Carbon::now()->format('Y-m-d H:i:s O'),
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
