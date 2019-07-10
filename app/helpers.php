<?php

/**
 * Parse a string as boolean.
 *
 * @param string $text
 * @return bool
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
 * Determines if a mobile number is anonymous.
 *
 * @param string $mobile
 * @return bool
 */
function is_anonymous_mobile($mobile)
{
    // @see https://support.twilio.com/hc/en-us/articles/223179988-Why-am-I-getting-calls-from-these-strange-numbers
    return in_array($mobile, [
        '+2562533',
        '+266696687',
        '+464',
        '+7378742883',
        '+86282452253',
        '+8656696',
    ]);
}
