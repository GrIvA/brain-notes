<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../../../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use App\Models\NoteModel;
use App\Models\SectionModel;
use App\Models\NotebookModel;
use App\Models\UserModel;

define('ROOTDIR', __DIR__ . '/../../../');
define('SETDIR', ROOTDIR . 'config/');
define('WEBDIR', ROOTDIR . 'public/');

// Mocking some environment for container
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$definitions = require SETDIR . 'container.php';
$builder = new \DI\ContainerBuilder();
$builder->addDefinitions($definitions);
$container = $builder->build();

$app = AppFactory::createFromContainer($container);

// Manual setup of some routes for testing
(require SETDIR . 'routes.php')($app);

$psr17Factory = new Psr17Factory();

// 1. Test for non-existent note
$request = $psr17Factory->createServerRequest('GET', '/note/99999');
$response = $app->handle($request);

echo "Test 404: " . ($response->getStatusCode() === 404 ? "PASSED" : "FAILED (" . $response->getStatusCode() . ")") . "\n";

// 2. Test for existent note (assuming ID 1 exists from previous generation)
$request = $psr17Factory->createServerRequest('GET', '/note/1');
$response = $app->handle($request);

echo "Test 200: " . ($response->getStatusCode() === 200 ? "PASSED" : "FAILED (" . $response->getStatusCode() . ")") . "\n";

$body = (string)$response->getBody();
echo "Contains Title: " . (strpos($body, 'Налаштування оточення') !== false ? "PASSED" : "FAILED") . "\n";
echo "Contains Markdown Rendered: " . (strpos($body, '<h1>Інструкція</h1>') !== false ? "PASSED" : "FAILED") . "\n";
echo "Control Panel hidden for anonymous: " . (strpos($body, 'Панель керування') === false ? "PASSED" : "FAILED") . "\n";

// 3. Test for Admin (manual controller call to avoid AuthMiddleware resetting attributes)
$userModel = $container->get(UserModel::class);
$adminData = $userModel->findById(1); // admin@example.com
$adminUser = new \App\Entities\User($adminData, $container->get(\App\Models\RegistryModel::class));

$controller = new \App\Controllers\Pages\NotePage($container);
$request = $psr17Factory->createServerRequest('GET', '/note/1')
    ->withAttribute('user', $adminUser);
$response = $psr17Factory->createResponse();

$response = $controller->handle($request, $response, ['id' => '1']);

$body = (string)$response->getBody();
echo "Control Panel visible for Admin: " . (strpos($body, 'Панель керування') !== false ? "PASSED" : "FAILED") . "\n";
