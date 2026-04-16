<?php

/**
 * Development specific configuration values.
 *
 * Every key must be set by its own to not overwrite the entire array.
 * Correct: $settings['db]['key'] = 'val'; $settings['db]['nextKey'] = 'nextVal';
 * Incorrect: $settings['db'] = [ 'key' => 'val', 'nextKey' => 'nextVal',];
 */

// Set false to show production error pages
$settings['dev'] = true;

// For the case where the error is not caught by custom error handler
ini_set('display_errors', $settings['dev'] ? '1' : '0');

// Display error details in browser and throw ErrorException for notices and warnings
$settings['error']['display_error_details'] = $settings['dev'];

// Disable template cache for dev
$settings['fenom']['options']['disable_cache'] = true;
$settings['fenom']['options']['strip'] = false;
