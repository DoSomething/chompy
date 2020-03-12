<?php

use Faker\Generator;
use Chompy\Models\RockTheVoteReport;

$factory->define(RockTheVoteReport::class, function (Generator $faker) {
    return [
        'id' => $faker->randomNumber(2),
        'status' => 'queued',
    ];
});
