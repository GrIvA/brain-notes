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
use App\Models\TagModel;
use Medoo\Medoo;

// Setup in-memory SQLite
$db = new Medoo(['type' => 'sqlite', 'database' => ':memory:']);

// Create tables
$db->query("CREATE TABLE notebooks (id INTEGER PRIMARY KEY, user_id INTEGER, title TEXT, attributes INTEGER, created_at DATETIME, updated_at DATETIME)");
$db->query("CREATE TABLE sections (id INTEGER PRIMARY KEY, notebook_id INTEGER, parent_id INTEGER, title TEXT, sort_order INTEGER, created_at DATETIME, updated_at DATETIME)");
$db->query("CREATE TABLE notes (id INTEGER PRIMARY KEY, section_id INTEGER, title TEXT, content TEXT, attributes INTEGER, created_at DATETIME, updated_at DATETIME)");
$db->query("CREATE TABLE tags (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, name TEXT, UNIQUE(user_id, name))");
$db->query("CREATE TABLE note_tags (note_id INTEGER, tag_id INTEGER, PRIMARY KEY (note_id, tag_id))");

$sectionModel = new SectionModel($db);
$noteModel = new NoteModel($db);
$tagModel = new TagModel($db);

$userId = 1;
$secId = $sectionModel->create(['notebook_id' => 1, 'title' => 'S1']);
$noteId = $noteModel->create(['section_id' => $secId, 'title' => 'N1', 'content' => 'C1']);

echo "Running tests for TagModel...\n";

// 1. Test: syncTags (Normalization)
$tagModel->syncTags((int)$noteId, $userId, ['PHP', '  #web  ', 'Php']); // Should result in [php, web]
$tags = $tagModel->getTagsByNoteId((int)$noteId);

if (count($tags) === 2) {
    $names = array_column($tags, 'name');
    if (in_array('php', $names) && in_array('web', $names)) {
        echo "✓ Test 1 Passed: Tags synchronized and normalized\n";
    } else {
        echo "✗ Test 1 Failed: Tags not normalized correctly: " . implode(', ', $names) . "\n";
    }
} else {
    echo "✗ Test 1 Failed: Expected 2 tags, got " . count($tags) . "\n";
}

// 2. Test: getAllUserTags
$allTags = $tagModel->getAllUserTags($userId);
if (count($allTags) === 2) {
    echo "✓ Test 2 Passed: getAllUserTags returned 2 tags\n";
} else {
    echo "✗ Test 2 Failed: Expected 2 tags, got " . count($allTags) . "\n";
}

// 3. Test: findNotesByTagIds (AND mode)
$noteId2 = $noteModel->create(['section_id' => $secId, 'title' => 'N2', 'content' => 'C2']);
$tagModel->syncTags((int)$noteId2, $userId, ['php']); // Note 2 only has 'php'

$phpTagId = $allTags[0]['name'] === 'php' ? $allTags[0]['id'] : $allTags[1]['id'];
$webTagId = $allTags[0]['name'] === 'web' ? $allTags[0]['id'] : $allTags[1]['id'];

// Search for BOTH php AND web (Should only find Note 1)
$results = $tagModel->findNotesByTagIds($userId, [(int)$phpTagId, (int)$webTagId], 'AND');
if (count($results) === 1 && $results[0]['id'] == $noteId) {
    echo "✓ Test 3 Passed: findNotesByTagIds (AND) found correct note\n";
} else {
    echo "✗ Test 3 Failed: AND search failed\n";
}

// 4. Test: findNotesByTagIds (OR mode)
// Search for EITHER php OR web (Should find both Note 1 and Note 2)
$db->query("INSERT INTO notebooks (id, user_id, title) VALUES (1, 1, 'NB1')"); // Need this for getUserSectionsSubquery
$resultsOr = $tagModel->findNotesByTagIds($userId, [(int)$webTagId], 'OR');
if (count($resultsOr) === 1 && $resultsOr[0]['id'] == $noteId) {
    echo "✓ Test 4 Passed: findNotesByTagIds (OR) found correct note\n";
} else {
    echo "✗ Test 4 Failed: OR search failed\n";
}

echo "Tests completed.\n";
