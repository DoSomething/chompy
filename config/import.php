<?php

return [
    'email_subscription' => [
        'topics' => [
            'community' => [
                'reset' =>  [
                    'enabled' => env('COMMUNITY_SUBSCRIPTION_RESET_ENABLED', 'true'),
                    'type' => env('COMMUNITY_SUBSCRIPTION_RESET_TYPE', 'wyd-activate-account'),
                ],
            ],
            'lifestyle' => [
                'reset' =>  [
                    'enabled' => env('LIFESTYLE_SUBSCRIPTION_RESET_ENABLED', 'true'),
                    'type' => env('LIFESTYLE_SUBSCRIPTION_RESET_TYPE', 'boost-activate-account'),
                ],
            ],
            'news' => [
                'reset' =>  [
                    'enabled' => env('NEWS_SUBSCRIPTION_RESET_ENABLED', 'true'),
                    'type' => env('NEWS_SUBSCRIPTION_RESET_TYPE', 'breakdown-activate-account'),
                ],
            ],
            'scholarships' => [
                'reset' =>  [
                    'enabled' => env('SCHOLARSHIPS_SUBSCRIPTION_RESET_ENABLED', 'true'),
                    'type' => env('SCHOLARSHIPS_SUBSCRIPTION_RESET_TYPE', 'pays-to-do-good-activate-account'),
                ],
            ],
        ],
    ],
    'rock_the_vote' => [
        'post' => [
            'action_id' => env('ROCK_THE_VOTE_POST_ACTION_ID', 954),
            'type' => env('ROCK_THE_VOTE_POST_TYPE', 'voter-reg'),
            'source' => env('ROCK_THE_VOTE_POST_SOURCE', 'rock-the-vote'),
        ],
        'reset' => [
            'enabled' => env('ROCK_THE_VOTE_RESET_ENABLED', 'true'),
            'type' => env('ROCK_THE_VOTE_RESET_TYPE', 'rock-the-vote-activate-account'),
        ],
        'user' => [
            'email_subscription_topics' => env('ROCK_THE_VOTE_EMAIL_SUBSCRIPTION_TOPICS', 'community'),
            'source_detail' => env('ROCK_THE_VOTE_USER_SOURCE_DETAIL', 'rock-the-vote'),
        ],
    ],
];
