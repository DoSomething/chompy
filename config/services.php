<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => Chompy\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'rogue' => [
        'url' => env('ROGUE_URL', 'https://rogue-qa.dosomething.org'),
    ],

    // 'northstar' => [
    //     'grant' => 'authorization_code', // Default OAuth grant to use: either 'authorization_code' or 'client_credentials'
    //     'url' => env('NORTHSTAR_URL'),
    //     'key' => storage_path('keys/public.key'),
    //     'bridge' => \DoSomething\Gateway\Laravel\LaravelOAuthBridge::class,
    //     'authorization_code' => [
    //         'client_id' => env('NORTHSTAR_AUTHORIZATION_ID'),
    //         'client_secret' => env('NORTHSTAR_AUTHORIZATION_SECRET'),
    //         'scope' => ['user', 'openid', 'role:staff', 'role:admin'],
    //         'redirect_uri' => 'next/login',
    //     ],
    // ],

    'northstar' => [
        'grant' => 'authorization_code', // Default OAuth grant to use: either 'authorization_code' or 'client_credentials'
        'url' => env('NORTHSTAR_URL'), // the environment you want to connect to
        'key' => storage_path('keys/public.key'),
        'bridge' => \DoSomething\Gateway\Laravel\LaravelOAuthBridge::class,
        'authorization_code' => [
            'client_id' => env('NORTHSTAR_AUTHORIZATION_ID'),
            'client_secret' => env('NORTHSTAR_AUTHORIZATION_SECRET'),
            'scope' => ['role:staff', 'role:admin'],
            'redirect_uri' => 'login',
        ],
        'client_credentials' => [
            'client_id' => env('NORTHSTAR_CLIENT_ID'),
            'client_secret' => env('NORTHSTAR_CLIENT_SECRET'),
            'scope' => ['admin', 'user', 'activity', 'write'],
        ],
    ],

];
