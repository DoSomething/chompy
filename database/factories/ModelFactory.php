<?php

use Faker\Generator;
use Chompy\Models\RockTheVoteReport;

// @TODO: Is this used?
$factory->define(Chompy\User::class, function (Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});

$factory->define(RockTheVoteReport::class, function (Generator $faker) {
    return [
        'id' => $faker->randomNumber(2),
        'status' => 'queued',
    ];
});
