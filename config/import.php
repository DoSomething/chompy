<?php

return [
    'email_subscription' => [
        // TODO: Use input values from the import form instead of hardcoding.
        'user' => [
            'email_subscription_topics' => 'news',
            'source_detail' => 'breakdown_opt_in',
        ],
    ],
    'rock_the_vote' => [
        'post' => [
            'action_id' => env('ROCK_THE_VOTE_POST_ACTION_ID', 850),
            'type' => env('ROCK_THE_VOTE_POST_TYPE', 'voter-reg'),
            'source' => env('ROCK_THE_VOTE_POST_SOURCE', 'rock-the-vote'),
        ],
        'reset' => [
            'enabled' => env('ROCK_THE_VOTE_RESET_ENABLED', 'true'),
            'type' => env('ROCK_THE_VOTE_RESET_TYPE', 'rock-the-vote-activate-account'),
        ],
        'user' => [
            'email_subscription_topics' => env('ROCK_THE_VOTE_EMAIL_SUBSCRIPTION_TOPICS', 'community'),
        ],
    ],
];
