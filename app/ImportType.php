<?php

namespace Chompy;

class ImportType
{
    /**
     * A Rock The Vote import type.
     *
     * @var string
     */
    public static $rockTheVote = 'rock-the-vote';

    /**
     * Returns list of all valid import types.
     *
     * @return array
     */
    public static function all()
    {
        return [self::$rockTheVote];
    }

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
