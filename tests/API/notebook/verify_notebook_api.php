<?php

declare(strict_types=1);

define('DS', DIRECTORY_SEPARATOR);
define('ROOTDIR', __DIR__ . '/../../../');
define('SETDIR', ROOTDIR . 'config' . DS);

require ROOTDIR . 'vendor/autoload.php';

use App\Models\NotebookModel;
use App\Models\SectionModel;
use Medoo\Medoo;

// Setup in-memory SQLite for integration test
$db = new Medoo([
    'type' => 'sqlite',
    'database' => ':memory:'
]);

// Create tables
$db->query("CREATE TABLE notebooks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    title TEXT,
    attributes INTEGER,
    created_at DATETIME,
    updated_at DATETIME
)");

$db->query("CREATE TABLE sections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    notebook_id INTEGER,
    parent_id INTEGER,
    title TEXT,
    sort_order INTEGER,
    created_at DATETIME,
    updated_at DATETIME
)");

$notebookModel = new NotebookModel($db);
$sectionModel = new SectionModel($db);

echo "Starting Integration Test for Notebook API...\n";

// 1. Create Notebook
$notebookId = $notebookModel->create([
    'user_id' => 1,
    'title' => 'My First Notebook',
    'attributes' => 1
]);
echo "✓ Notebook created with ID: $notebookId\n";

// 2. Create Sections Hierarchy
// Root 1
//   - Child 1.1
//     - Grandchild 1.1.1
// Root 2

$root1Id = $sectionModel->create(['notebook_id' => $notebookId, 'title' => 'Root 1', 'parent_id' => null]);
$child11Id = $sectionModel->create(['notebook_id' => $notebookId, 'title' => 'Child 1.1', 'parent_id' => $root1Id]);
$grandchild111Id = $sectionModel->create(['notebook_id' => $notebookId, 'title' => 'Grandchild 1.1.1', 'parent_id' => $child11Id]);
$root2Id = $sectionModel->create(['notebook_id' => $notebookId, 'title' => 'Root 2', 'parent_id' => null]);

echo "✓ Hierarchy created.\n";

// 3. Verify Tree
$tree = $sectionModel->getTree((int)$notebookId);

if (count($tree) === 2 && $tree[0]['title'] === 'Root 1' && count($tree[0]['children']) === 1) {
    echo "✓ Tree structure is correct.\n";
} else {
    echo "✗ Tree structure is incorrect.\n";
    print_r($tree);
}

// 4. Test Move (Move Grandchild to Root 2)
if ($sectionModel->move((int)$grandchild111Id, (int)$root2Id)) {
    $updatedTree = $sectionModel->getTree((int)$notebookId);
    // Root 2 should now have 1 child
    if (count($updatedTree[1]['children'] ?? []) === 1 && $updatedTree[1]['children'][0]['title'] === 'Grandchild 1.1.1') {
        echo "✓ Move operation successful.\n";
    } else {
        echo "✗ Move operation failed validation.\n";
    }
} else {
    echo "✗ Move operation failed.\n";
}

// 5. Test Delete
$sectionModel->delete((int)$root1Id);
$finalTree = $sectionModel->getTree((int)$notebookId);
if (count($finalTree) === 1 && $finalTree[0]['title'] === 'Root 2') {
    echo "✓ Delete operation successful.\n";
} else {
    echo "✗ Delete operation failed.\n";
}

echo "Integration tests completed.\n";
