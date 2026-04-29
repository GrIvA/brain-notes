<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../../../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use App\Models\NoteModel;
use App\Models\TagModel;
use App\Models\UserModel;

define('ROOTDIR', __DIR__ . '/../../../');
define('SETDIR', ROOTDIR . 'config/');
define('WEBDIR', ROOTDIR . 'public/');

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$definitions = require SETDIR . 'container.php';
$builder = new \DI\ContainerBuilder();
$builder->addDefinitions($definitions);
$container = $builder->build();

$app = AppFactory::createFromContainer($container);
(require SETDIR . 'routes.php')($app);

$psr17Factory = new Psr17Factory();
$tagModel = $container->get(TagModel::class);
$noteModel = $container->get(NoteModel::class);

// Setup: Create 2 notes with specific tags
$userId = 5; // bn@griva.top
$noteId1 = 1;
$noteId2 = 2;

// Tag ID 1 and 2
$tagModel->syncTags($noteId1, $userId, ['PHP', 'Slim']);
$tagModel->syncTags($noteId2, $userId, ['PHP', 'Alpine']);

// Test 1: Search by tag PHP (should find both)
$criteria = ['tag_ids' => [1], 'user_id' => $userId]; // Assuming tag PHP is ID 1
$notes = $noteModel->findFiltered($criteria);
echo "Search by 'PHP': Found " . count($notes) . " notes (Expected >= 2)\n";

// Test 2: Search by PHP AND Slim (should find only note 1)
// We need to know actual IDs
$allTags = $tagModel->getAllUserTags($userId);
$tagIds = [];
foreach ($allTags as $t) {
    if ($t['name'] == 'php') $tagIds['php'] = $t['id'];
    if ($t['name'] == 'slim') $tagIds['slim'] = $t['id'];
    if ($t['name'] == 'alpine') $tagIds['alpine'] = $t['id'];
}

$criteria = ['tag_ids' => [$tagIds['php'], $tagIds['slim']], 'user_id' => $userId];
$notes = $noteModel->findFiltered($criteria);
echo "Search by 'PHP' AND 'Slim': Found " . count($notes) . " notes (Expected 1)\n";

// Test 3: API route for list filtering
$request = $psr17Factory->createServerRequest('GET', '/api/v1/notes/list')
    ->withQueryParams(['tag_ids' => [$tagIds['php'], $tagIds['alpine']]]);

// Mock Auth
$userModel = $container->get(UserModel::class);
$userData = $userModel->findById($userId);
$user = new \App\Entities\User($userData, $container->get(\App\Models\RegistryModel::class));
$request = $request->withAttribute('user', $user);

$response = $app->handle($request);
echo "API List Filter Status: " . ($response->getStatusCode() === 200 ? "PASSED" : "FAILED") . "\n";
$body = (string)$response->getBody();
echo "API Body contains note 2: " . (strpos($body, 'Usage Examples') !== false ? "PASSED" : "FAILED") . "\n";
echo "API Body NOT contains note 1: " . (strpos($body, 'Налаштування оточення') === false ? "PASSED" : "FAILED") . "\n";
