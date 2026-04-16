<?php
/*
 * Thaks for pretty slim starter project
 * https://samuel-gfeller.ch/docs/
 */

use DI\ContainerBuilder;
use Slim\App;

define('DS', DIRECTORY_SEPARATOR);
define('WEBDIR', dirname(__FILE__) .DS);
define('ROOTDIR', dirname(WEBDIR) .DS);
define('VENDIR', ROOTDIR . 'vendor' . DS);
define('CLASSDIR', ROOTDIR . 'classes' . DS);
define('SETDIR', ROOTDIR . 'config' . DS);

// set Composer autoloader
require VENDIR . 'autoload.php';

// Instantiate DI ContainerBuilder
$containerBuilder = new ContainerBuilder();
// Add container definitions and build DI container
$container = $containerBuilder->addDefinitions(SETDIR . '/container.php')->build();

// Create app instance
$app = $container->get(App::class);

$app->run();
