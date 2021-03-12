<?php

use Illuminate\Support\Str;

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

/**
 * Determines if a mobile number is valid.
 *
 * @TODO: Add phone number validation lib, or DRY this with Northstar validation through Gateway.
 * @see https://git.io/Jvy0A
 * For now, RTV is passing through a 000-000-0000 number that causes user creation to fail.
 * @see https://github.com/DoSomething/chompy/pull/140
 *
 * @param string $mobile
 * @return bool
 */
function is_valid_mobile($mobile)
{
    // This phone number has been passed before and fails Northstar validation.
    if ($mobile == '000-000-0000') {
        return false;
    }

    /**
     * Remove spaces and dashes and make sure there are at least 10 digits, e.g. "787 249 13" has
     * been passed and fails Northstar validation.
     */
    $mobile = preg_replace("/[\s-]+/", '', $mobile);

    return strlen($mobile) > 9;
}

/**
 * Parses the data of given record in the failed_jobs DB table.
 *
 * @param object $failedJob
 * @return array
 */
function parse_failed_job($failedJob)
{
    $json = json_decode($failedJob->payload);
    $command = unserialize($json->data->command);

    return [
        'id' => $failedJob->id,
        'failedAt' => $failedJob->failed_at,
        'commandName' => $json->data->commandName,
        'errorMessage' => Str::limit($failedJob->exception, 255),
        'parameters' => method_exists($command, 'getParameters') ? $command->getParameters() : [],
    ];
}
