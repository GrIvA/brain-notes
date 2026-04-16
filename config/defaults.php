<?php

/**
 * Default configuration values.
 *
 * This file should contain all keys, even secret ones to serve as template.
 *
 * This is the first file loaded in settings.php and can safely define arrays
 * without the risk of overwriting something.
 * The only file where the following is permitted: $settings['db'] = ['key' => 'val', 'nextKey' => 'nextVal'];
 *
 */
// Set default locale
setlocale(LC_ALL, 'en_US.utf8', 'en_US');

// Init settings var
$settings = [];


$settings['deployment'] = [
    // Version string or null.
    // If JsImportCacheBuster is enabled, `null` removes all query param versions from js imports
    'version' => '1.0.1',
    // When true, JsImportCacheBuster is enabled and goes through all js files and changes the version number
    // from the imports. Should be disabled in env.prod.php.
    // https://samuel-gfeller.ch/docs/Template-Rendering#js-import-cache-busting
    'update_js_imports_version' => false,
    // Asset path required by the JsImportCacheBuster
    'asset_path' => ROOTDIR . '/public/assets',
];

// Error handler: https://github.com/samuelgfeller/slim-error-renderer
$settings['error'] = [
    // MUST be set to false in production.
    // When set to true, it shows error details and throws an ErrorException for notices and warnings.
    'display_error_details' => false,
    'log_errors' => true,
];

$settings['public'] = [
    'app_name' => 'Brain Notes',
    'main_contact_email' => 'bn_contact@griva.org.ua',
];

// Secret values are overwritten in env.php
$settings['db_connect'] = [
    'type' => 'sqlite',
    'database' => ROOTDIR . 'storage/db/database.db',
    'error' => PDO::ERRMODE_SILENT,
    'host' => 'localhost',
];

$settings['logger'] = [
    // Log file location
    'path' => ROOTDIR . 'logs',
    'table' => 'log',
];

$settings['languages'] = [
    'default_id' => 1,
    'table' => 'languages',
    'table' => 'languages',
    'file_mask' => 'bn_###.xml',
    'file_path' => SETDIR,
];

$settings['fenom'] = [
     'template_dir' => 'templates/',
     'cache_dir' => 'tmp',
     'options' => [
         'auto_reload' => true,
         'force_include' => true,
         'disable_cache' => false,  // comment this for production
         'strip' => true
     ]
];

return $settings;
