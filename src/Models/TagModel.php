<?php

declare(strict_types=1);

namespace App\Models;

use Medoo\Medoo;

class TagModel
{
    private Medoo $db;
    private string $tableTags = 'tags';
    private string $tableNoteTags = 'note_tags';

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    /**
     * Get all tags for a specific user.
     * Useful for autocomplete.
     */
    public function getAllUserTags(int $userId): array
    {
        return $this->db->select($this->tableTags, ['id', 'name'], [
            'user_id' => $userId,
            'ORDER' => ['name' => 'ASC']
        ]);
    }

    /**
     * Add a single tag to a note by name.
     */
    public function addTagToNote(int $noteId, int $userId, string $tagName): bool
    {
        $tagName = mb_strtolower(trim(str_replace('#', '', $tagName)));
        if (empty($tagName)) return false;

        // 1. Get or create tag
        $tag = $this->db->get($this->tableTags, ['id'], [
            'user_id' => $userId,
            'name' => $tagName
        ]);

        if (!$tag) {
            $this->db->insert($this->tableTags, [
                'user_id' => $userId,
                'name' => $tagName
            ]);
            $tagId = (int)$this->db->id();
        } else {
            $tagId = (int)$tag['id'];
        }

        // 2. Link to note if not already linked
        $exists = $this->db->has($this->tableNoteTags, [
            'note_id' => $noteId,
            'tag_id' => $tagId
        ]);

        if (!$exists) {
            $this->db->insert($this->tableNoteTags, [
                'note_id' => $noteId,
                'tag_id' => $tagId
            ]);
            return true;
        }

        return false;
    }

    /**
     * Sync tags for a note.
     * Creates new tags if they don't exist.
     */
    public function syncTags(int $noteId, int $userId, array $tagNames): void
    {
        // 1. Normalize tag names
        $normalizedTags = array_unique(array_map(function ($name) {
            return mb_strtolower(trim(str_replace('#', '', $name)));
        }, $tagNames));
        $normalizedTags = array_filter($normalizedTags);

        // 2. Get existing tags for this user
        $existingTags = $this->db->select($this->tableTags, ['id', 'name'], [
            'user_id' => $userId,
            'name' => $normalizedTags
        ]);
        
        $existingNames = array_column($existingTags, 'name');
        $tagMap = array_combine($existingNames, array_column($existingTags, 'id'));

        // 3. Create missing tags
        foreach ($normalizedTags as $tagName) {
            if (!isset($tagMap[$tagName])) {
                $this->db->insert($this->tableTags, [
                    'user_id' => $userId,
                    'name' => $tagName
                ]);
                $tagMap[$tagName] = (int)$this->db->id();
            }
        }

        // 4. Get current note tags
        $currentTagIds = array_values($tagMap);

        // 5. Update note_tags mapping
        // Simplest way: delete all and re-insert
        $this->db->delete($this->tableNoteTags, ['note_id' => $noteId]);
        
        if (!empty($currentTagIds)) {
            $data = [];
            foreach ($currentTagIds as $tagId) {
                $data[] = [
                    'note_id' => $noteId,
                    'tag_id' => $tagId
                ];
            }
            $this->db->insert($this->tableNoteTags, $data);
        }
    }

    /**
     * Remove a specific tag from a note.
     */
    public function removeTagFromNote(int $noteId, int $tagId): bool
    {
        $result = $this->db->delete($this->tableNoteTags, [
            'note_id' => $noteId,
            'tag_id' => $tagId
        ]);
        return $result->rowCount() > 0;
    }

    /**
     * Get tags assigned to a specific note.
     */
    public function getTagsByNoteId(int $noteId): array
    {
        return $this->db->select($this->tableNoteTags, [
            "[>]tags" => ["tag_id" => "id"]
        ], [
            "tags.id",
            "tags.name"
        ], [
            "note_tags.note_id" => $noteId
        ]);
    }

    /**
     * Find notes by tag IDs.
     * Mode 'AND' finds notes containing ALL tags.
     * Mode 'OR' finds notes containing ANY of the tags.
     */
    public function findNotesByTagIds(int $userId, array $tagIds, string $mode = 'AND'): array
    {
        if (empty($tagIds)) {
            return [];
        }

        if ($mode === 'OR') {
            $noteIds = $this->db->select($this->tableNoteTags, "note_id", [
                "tag_id" => $tagIds
            ]);

            if (empty($noteIds)) {
                return [];
            }

            return $this->db->select('notes', '*', [
                'id' => $noteIds,
                'section_id' => $this->getUserSectionsSubquery($userId)
            ]);
        }

        // AND mode: Intersection
        $total = count($tagIds);
        $query = "SELECT note_id FROM {$this->tableNoteTags} 
                  WHERE tag_id IN (" . implode(',', array_map('intval', $tagIds)) . ") 
                  GROUP BY note_id 
                  HAVING COUNT(tag_id) = {$total}";
        
        $noteIds = $this->db->query($query)->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($noteIds)) {
            return [];
        }

        return $this->db->select('notes', '*', ['id' => $noteIds]);
    }

    /**
     * Helper to get section IDs belonging to a user.
     */
    private function getUserSectionsSubquery(int $userId): array
    {
        $notebookIds = $this->db->select('notebooks', 'id', ['user_id' => $userId]);
        if (empty($notebookIds)) return [-1];
        
        $sectionIds = $this->db->select('sections', 'id', ['notebook_id' => $notebookIds]);
        return empty($sectionIds) ? [-1] : $sectionIds;
    }
}
