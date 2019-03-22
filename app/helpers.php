<?php

use Illuminate\Support\Str;

/**
 * Returns whether email is a test email.
 * TODO: This isn't used anywhere, although deprecated jobs could use it to DRY. Remove it all?
 * @see https://www.pivotaltracker.com/story/show/164114650
 *
 * @param string $email
 * @return boolean
 */
function is_test_email($email)
{
    return Str::contains($email, ['@dosomething.org', '@example']);
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

/**
 * Returns source of a user created by import.
 *
 * @return string
 */
function get_user_source(){
  return config('services.northstar.client_credentials.client_id');
}
