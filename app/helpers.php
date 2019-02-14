<?php

/**
 * Returns import type of Facebook share files.
 */
function get_import_type_facebook()
{
  return 'facebook';
}

/**
 * Returns import type of Rock the Vote files.
 * @return string
 */
function get_import_type_rock_the_vote()
{
  return 'rock-the-vote';
}

/**
 * Returns import type of Turbovote files.
 * @return string
 */
function get_import_type_turbovote()
{
  return 'turbovote';
}

/**
 * Returns array of supported import types.
 * @return array
 */
function get_import_types()
{
    return [
      get_import_type_facebook(),
      get_import_type_rock_the_vote(),
      get_import_type_turbovote(),
    ];
}

/**
 * Parse a string as boolean.
 *
 * @param string $text
 * @return boolean
 */
function str_to_boolean($text)
{
    $sanitized = strtolower($text);

    if ($sanitized === 'y') {
        return true;
    }

    return filter_var($sanitized, FILTER_VALIDATE_BOOLEAN);
}
