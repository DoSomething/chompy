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
    public static function all() {
        return [self::$facebook, self::$rockTheVote, self::$turbovote];
    }
}
