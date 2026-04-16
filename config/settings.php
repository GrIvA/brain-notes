<?php

/**
 * This file loads the default and environment-specific settings.
 */

// Load default settings
// MUST NOT be require_once otherwise test settings are included only once and not again for the next tests
$settings = require SETDIR . 'defaults.php';

// Load secret configuration
if (file_exists(SETDIR . 'env/env.php')) {
    require SETDIR . '/env/env.php';
}

return $settings;
