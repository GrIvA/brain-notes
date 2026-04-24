<?php

declare(strict_types=1);

define('DS', DIRECTORY_SEPARATOR);
define('ROOTDIR', __DIR__ . '/../../../');
define('SETDIR', ROOTDIR . 'config' . DS);

require ROOTDIR . 'vendor/autoload.php';

use App\Models\SectionModel;
use App\Models\NoteModel;
use Medoo\Medoo;

// Setup in-memory SQLite for testing
$db = new Medoo([
    'type' => 'sqlite',
    'database' => ':memory:'
]);

// Create tables
$db->query("CREATE TABLE sections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    notebook_id INTEGER,
    parent_id INTEGER,
    title TEXT,
    sort_order INTEGER,
    created_at DATETIME,
    updated_at DATETIME
)");

$db->query("CREATE TABLE notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    section_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    attributes INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
)");

$sectionModel = new SectionModel($db);
$noteModel = new NoteModel($db);

echo "Running tests for NoteModel and SectionModel integration...\n";

// 1. Setup sections
$secA = $sectionModel->create(['notebook_id' => 1, 'title' => 'Section A']);
$secB = $sectionModel->create(['notebook_id' => 1, 'title' => 'Section B']);

// 2. Test: Create Note
$noteId1 = $noteModel->create([
    'section_id' => $secA,
    'title' => 'Note 1',
    'content' => 'Content 1',
    'attributes' => NoteModel::ATTR_PUBLIC
]);

if ($noteId1) {
    echo "✓ Test 1 Passed: Note created with ID $noteId1\n";
} else {
    echo "✗ Test 1 Failed: Note not created\n";
    exit(1);
}

// 3. Test: Forbidden Section Deletion
if (!$sectionModel->delete((int)$secA)) {
    echo "✓ Test 2 Passed: Forbidden deletion of non-empty section\n";
} else {
    echo "✗ Test 2 Failed: Non-empty section was deleted\n";
    exit(1);
}

// 4. Test: moveNotes (Single/List)
$noteId2 = $noteModel->create(['section_id' => $secA, 'title' => 'Note 2', 'content' => 'C2']);
$movedCount = $noteModel->moveNotes([(int)$noteId1, (int)$noteId2], (int)$secB);

if ($movedCount === 2) {
    $n1 = $noteModel->findById((int)$noteId1);
    if ($n1['section_id'] == $secB) {
        echo "✓ Test 3 Passed: Batch move of 2 notes successful\n";
    } else {
        echo "✗ Test 3 Failed: Note 1 not in Section B\n";
    }
} else {
    echo "✗ Test 3 Failed: Moved $movedCount notes instead of 2\n";
}

// 5. Test: migrateAll
$noteId3 = $noteModel->create(['section_id' => $secA, 'title' => 'Note 3', 'content' => 'C3']);
$migratedCount = $noteModel->migrateAll((int)$secA, (int)$secB);

if ($migratedCount === 1) {
    $n3 = $noteModel->findById((int)$noteId3);
    if ($n3['section_id'] == $secB) {
        echo "✓ Test 4 Passed: migrateAll successful\n";
    } else {
        echo "✗ Test 4 Failed: Note 3 not in Section B\n";
    }
} else {
    echo "✗ Test 4 Failed: Migrated $migratedCount notes instead of 1\n";
}

// 6. Test: Allow Section Deletion when empty
if ($sectionModel->delete((int)$secA)) {
    echo "✓ Test 5 Passed: Empty section deleted\n";
} else {
    echo "✗ Test 5 Failed: Could not delete empty section\n";
}

echo "Tests completed.\n";
