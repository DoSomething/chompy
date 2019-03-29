<?php

namespace Chompy;

class ImportType
{
    /**
     * An email import.
     *
     * @var string
     */
    public static $email = 'email';

    /**
     * A Rock The Vote import.
     *
     * @var string
     */
    public static $rockTheVote = 'rock-the-vote';

    /**
     * Returns config array of given import type.
     *
     * @return array
     */
    public static function getConfig($type)
    {
        if ($type === self::$rockTheVote) {
            return config('import.rock_the_vote');
        }

        throw new HttpException(500, 'Config not found for type '.$type.'.');
    }
}
