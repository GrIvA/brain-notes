<?php

declare(strict_types=1);

define('DS', DIRECTORY_SEPARATOR);
define('ROOTDIR', __DIR__ . '/../../../');
define('SETDIR', ROOTDIR . 'config' . DS);

require ROOTDIR . 'vendor/autoload.php';

use App\Models\SectionModel;
use Medoo\Medoo;

// Setup in-memory SQLite for testing
$db = new Medoo([
    'type' => 'sqlite',
    'database' => ':memory:'
]);

// Create table
$db->query("CREATE TABLE sections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    notebook_id INTEGER,
    parent_id INTEGER,
    title TEXT,
    sort_order INTEGER,
    created_at DATETIME,
    updated_at DATETIME
)");

$model = new SectionModel($db);

// Helper to create sections
function create($model, $title, $parentId = null) {
    return $model->create([
        'notebook_id' => 1,
        'title' => $title,
        'parent_id' => $parentId
    ]);
}

echo "Running tests for SectionModel::move...\n";

// 1. Setup hierarchy: A -> B -> C
$idA = create($model, "A");
$idB = create($model, "B", $idA);
$idC = create($model, "C", $idB);

// Test 1: Simple move (C -> A)
if ($model->move((int)$idC, (int)$idA)) {
    echo "✓ Test 1 Passed: Simple move C -> A\n";
} else {
    echo "✗ Test 1 Failed: Simple move C -> A\n";
}

// Test 2: Prevent move to self (A -> A)
if (!$model->move((int)$idA, (int)$idA)) {
    echo "✓ Test 2 Passed: Prevent move A -> A\n";
} else {
    echo "✗ Test 2 Failed: Prevent move A -> A\n";
}

// Test 3: Prevent cycle (A -> B, where B is child of A)
// Hierarchy now is: A -> C, A -> B -> (A?)
if (!$model->move((int)$idA, (int)$idB)) {
    echo "✓ Test 3 Passed: Prevent cycle A -> B\n";
} else {
    echo "✗ Test 3 Failed: Prevent cycle A -> B\n";
}

// Test 4: Prevent cycle deep (A -> C, where C is child of B which is child of A)
if (!$model->move((int)$idA, (int)$idC)) {
    echo "✓ Test 4 Passed: Prevent cycle A -> C\n";
} else {
    echo "✗ Test 4 Failed: Prevent cycle A -> C\n";
}

echo "Tests completed.\n";
