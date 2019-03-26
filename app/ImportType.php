<?php

namespace Chompy;

class ImportType
{
    /**
     * A Facebook share import type.
     *
     * @var string
     */
    public static $facebook = 'facebook';

    /**
     * A Rock The Vote import type.
     *
     * @var string
     */
    public static $rockTheVote = 'rock-the-vote';

    /**
     * A Turbovote import type.
     *
     * @var string
     */
    public static $turbovote = 'turbovote';

    /**
     * Returns list of all valid import types.
     *
     * @return array
     */
    public static function all()
    {
        return [self::$facebook, self::$rockTheVote, self::$turbovote];
    }

    /**
     * Returns config array of given import type.
     *
     * @return array
     */
    public static function getVars($type)
    {
        if ($type === self::$rockTheVote) {
            return [
                'title' => 'Rock The Vote',
                'config' => config('import.rock_the_vote'),
            ];
        }

        throw new HttpException(500, 'Config not found for type '.$type.'.');
    }
}
