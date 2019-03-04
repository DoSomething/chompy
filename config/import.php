<?php

return [
    'rock_the_vote' => [
        'post' => [
            'action_id' => env('ROCK_THE_VOTE_POST_ACTION_ID', 850),
            'type' => env('ROCK_THE_VOTE_POST_TYPE', 'voter-reg'),
            'source' => env('ROCK_THE_VOTE_POST_SOURCE', 'rock-the-vote'),
        ],
    ],
];
