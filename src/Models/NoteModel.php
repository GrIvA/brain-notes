<?php

declare(strict_types=1);

namespace App\Models;

use Medoo\Medoo;

class NoteModel
{
    private Medoo $db;
    private string $table = 'notes';

    public const ATTR_PUBLIC = 1;
    public const ATTR_PINNED = 2;
    public const ATTR_DRAFT  = 4;

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?array
    {
        return $this->db->get($this->table, '*', ['id' => $id]);
    }

    public function findBySectionId(int $sectionId): array
    {
        return $this->db->select($this->table, '*', ['section_id' => $sectionId]);
    }

    /**
     * Find notes with filtering by tags, section, or notebook.
     * Supports combined visibility (owner + public).
     */
    public function findFiltered(array $criteria): array
    {
        $where = [];

        // 1. Core visibility condition
        if (!empty($criteria['user_id'])) {
            // Owner's notes OR public notes
            $notebookIds = $this->db->select('notebooks', 'id', ['user_id' => (int)$criteria['user_id']]);
            $sectionIds = !empty($notebookIds) ? $this->db->select('sections', 'id', ['notebook_id' => $notebookIds]) : [];
            
            // Subquery for public notes to avoid complex Medoo OR with bitwise
            $publicNoteIds = $this->db->query("SELECT id FROM notes WHERE (attributes & " . self::ATTR_PUBLIC . ") = " . self::ATTR_PUBLIC)->fetchAll(\PDO::FETCH_COLUMN);

            $where['OR'] = [
                'notes.section_id' => !empty($sectionIds) ? $sectionIds : [-1],
                'notes.id' => !empty($publicNoteIds) ? $publicNoteIds : [-1]
            ];
        } else {
            // Guest: ONLY public notes
            $publicNoteIds = $this->db->query("SELECT id FROM notes WHERE (attributes & " . self::ATTR_PUBLIC . ") = " . self::ATTR_PUBLIC)->fetchAll(\PDO::FETCH_COLUMN);
            $where['notes.id'] = !empty($publicNoteIds) ? $publicNoteIds : [-1];
        }

        // 2. Section filtering (narrow down visibility)
        if (!empty($criteria['section_id'])) {
            // If section is provided, it must be within the visibility scope
            $where['notes.section_id'] = $criteria['section_id'];
        }

        // 3. Tag filtering
        if (!empty($criteria['tag_ids'])) {
            $tagIds = array_map('intval', (array)$criteria['tag_ids']);
            $total = count($tagIds);
            
            // Subquery to find notes that have ALL requested tags
            $query = "SELECT note_id FROM note_tags 
                      WHERE tag_id IN (" . implode(',', $tagIds) . ") 
                      GROUP BY note_id 
                      HAVING COUNT(tag_id) = {$total}";
            
            $noteIds = $this->db->query($query)->fetchAll(\PDO::FETCH_COLUMN);
            
            if (empty($noteIds)) {
                return [];
            }
            $where['notes.id'] = $noteIds;
        }

        $where['ORDER'] = ['notes.created_at' => 'DESC'];

        return $this->db->select($this->table, [
            "[>]sections" => ["section_id" => "id"],
            "[>]notebooks" => ["sections.notebook_id" => "id"]
        ], [
            "notes.id",
            "notes.section_id",
            "notes.title",
            "notes.content",
            "notes.attributes",
            "notes.created_at",
            "notes.updated_at",
            "notebooks.user_id"
        ], $where);
    }

    public function create(array $data): int|string|null
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->id();
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $result = $this->db->update($this->table, $data, ['id' => $id]);
        return $result->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $result = $this->db->delete($this->table, ['id' => $id]);
        return $result->rowCount() > 0;
    }

    /**
     * Move specific notes to a target section.
     *
     * @param array $noteIds Array of note IDs
     * @param int $targetSectionId Target section ID
     * @return int Number of moved notes
     */
    public function moveNotes(array $noteIds, int $targetSectionId): int
    {
        if (empty($noteIds)) {
            return 0;
        }

        $result = $this->db->update($this->table, [
            'section_id' => $targetSectionId,
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'id' => $noteIds
        ]);

        return (int)$result->rowCount();
    }

    /**
     * Migrate all notes from one section to another.
     *
     * @param int $sourceSectionId Source section ID
     * @param int $targetSectionId Target section ID
     * @return int Number of migrated notes
     */
    public function migrateAll(int $sourceSectionId, int $targetSectionId): int
    {
        $result = $this->db->update($this->table, [
            'section_id' => $targetSectionId,
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'section_id' => $sourceSectionId
        ]);

        return (int)$result->rowCount();
    }

    /**
     * Count notes in a section.
     *
     * @param int $sectionId
     * @return int
     */
    public function countBySectionId(int $sectionId): int
    {
        return $this->db->count($this->table, ['section_id' => $sectionId]);
    }
}
