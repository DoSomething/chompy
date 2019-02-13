<?php

/**
 * Parse a CSV field value as boolean.
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
