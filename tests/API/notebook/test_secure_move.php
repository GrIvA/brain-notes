<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('DS', DIRECTORY_SEPARATOR);
define('ROOTDIR', __DIR__ . '/../../../');
define('SETDIR', ROOTDIR . 'config' . DS);

require ROOTDIR . 'vendor/autoload.php';

use App\Models\SectionModel;
use App\Models\NoteModel;
use App\Models\NotebookModel;
use App\Controllers\Api\NoteController;
use App\Entities\User;
use Medoo\Medoo;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;

// Mock Container simple implementation
class SimpleContainer implements \Psr\Container\ContainerInterface {
    private $services = [];
    public function set($id, $service) { $this->services[$id] = $service; }
    public function get(string $id) { return $this->services[$id] ?? null; }
    public function has(string $id): bool { return isset($this->services[$id]); }
}

// Setup in-memory SQLite
$db = new Medoo(['type' => 'sqlite', 'database' => ':memory:']);
$db->query("CREATE TABLE notebooks (id INTEGER PRIMARY KEY, user_id INTEGER, title TEXT, attributes INTEGER, created_at DATETIME, updated_at DATETIME)");
$db->query("CREATE TABLE sections (id INTEGER PRIMARY KEY, notebook_id INTEGER, parent_id INTEGER, title TEXT, sort_order INTEGER, created_at DATETIME, updated_at DATETIME)");
$db->query("CREATE TABLE notes (id INTEGER PRIMARY KEY, section_id INTEGER, title TEXT, content TEXT, attributes INTEGER, created_at DATETIME, updated_at DATETIME)");

$notebookModel = new NotebookModel($db);
$sectionModel = new SectionModel($db);
$noteModel = new NoteModel($db);

$container = new SimpleContainer();
$container->set(NoteModel::class, $noteModel);
$container->set(SectionModel::class, $sectionModel);
$container->set(NotebookModel::class, $notebookModel);
$container->set('settings', []);
$container->set('dbase', $db);
$container->set(\Analog\Logger::class, new \Analog\Logger());

$controller = new NoteController($container);

// Setup users and data
$userA_id = 1;
$userB_id = 2;

$nbA = $notebookModel->create(['user_id' => $userA_id, 'title' => 'NB A']);
$secA = $sectionModel->create(['notebook_id' => $nbA, 'title' => 'Sec A']);
$noteA = $noteModel->create(['section_id' => $secA, 'title' => 'Note A', 'content' => 'x']);

$nbB = $notebookModel->create(['user_id' => $userB_id, 'title' => 'NB B']);
$secB = $sectionModel->create(['notebook_id' => $nbB, 'title' => 'Sec B']);
$noteB = $noteModel->create(['section_id' => $secB, 'title' => 'Note B', 'content' => 'y']);

echo "Running security tests for NoteController::move...\n";

// Mock User A
class MockUser extends User {
    protected int $id;
    public function __construct(int $id) { $this->id = $id; }
    public function getId(): int { return $this->id; }
}
$userA = new MockUser($userA_id);

// Case 1: User A tries to move his own note (Success)
$req = (new ServerRequest('PATCH', '/api/v1/notes/move'))
    ->withAttribute('user', $userA)
    ->withParsedBody(['target_section_id' => $secA, 'note_ids' => [$noteA]]);

$res = $controller->move($req, new Response());
if ($res->getStatusCode() === 200) {
    echo "✓ Case 1 Passed: Own note move allowed\n";
} else {
    echo "✗ Case 1 Failed: Own note move denied status: " . $res->getStatusCode() . "\n";
}

// Case 2: User A tries to move User B's note (Denied)
$req = (new ServerRequest('PATCH', '/api/v1/notes/move'))
    ->withAttribute('user', $userA)
    ->withParsedBody(['target_section_id' => $secA, 'note_ids' => [$noteB]]);

$res = $controller->move($req, new Response());
if ($res->getStatusCode() === 403) {
    echo "✓ Case 2 Passed: Foreign note move denied (403)\n";
} else {
    echo "✗ Case 2 Failed: Foreign note move allowed or wrong status code: " . $res->getStatusCode() . "\n";
}

echo "Security tests completed.\n";
