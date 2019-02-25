<?php

use Illuminate\Support\Str;

/**
 * Returns whether email is a test email.
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
