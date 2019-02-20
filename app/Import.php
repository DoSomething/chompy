<?php

namespace Chompy;

class ImportType {
  public static $facebook = 'facebook';
  public static $rockTheVote = 'rock-the-vote';
  public static $turbovote = 'turbovote';

  public static function all() {
    return [self::$facebook, self::$rockTheVote, self::$turbovote];
  }
}
