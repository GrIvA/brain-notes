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
