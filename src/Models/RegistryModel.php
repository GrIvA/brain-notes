<?php

declare(strict_types=1);

namespace App\Models;

use Medoo\Medoo;

class RegistryModel
{
    private Medoo $db;
    private string $table = 'registry';

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    /**
     * Fetch all non-deleted tags for a specific entity and optional endpoint.
     *
     * @param string $tagType
     * @param int $entityId
     * @param string|null $endpoint
     * @return array
     */
    public function fetchTags(string $tagType, int $entityId, ?string $endpoint = null): array
    {
        $where = [
            'tag_type' => $tagType,
            'entity_id' => $entityId,
            'deleted_at' => null,
        ];

        if ($endpoint !== null) {
            $where['endpoint'] = $endpoint;
        }

        return $this->db->select($this->table, '*', [
            'AND' => $where,
            'ORDER' => ['sort_order' => 'ASC', 'id' => 'ASC']
        ]) ?: [];
    }

    /**
     * Save or update a tag.
     *
     * @param array $data
     * @return int|string|null
     */
    public function saveTag(array $data): int|string|null
    {
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update($this->table, $data, ['id' => $id]);
            return $id;
        }

        $this->db->insert($this->table, $data);
        return $this->db->id();
    }

    /**
     * Soft delete a tag by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteTag(int $id): bool
    {
        $result = $this->db->update($this->table, [
            'deleted_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);

        return $result->rowCount() > 0;
    }

    /**
     * Hard delete tags (e.g. for cleanup).
     *
     * @return int
     */
    public function purgeDeleted(): int
    {
        $result = $this->db->delete($this->table, [
            'deleted_at[!]' => null
        ]);

        return $result->rowCount();
    }
}
