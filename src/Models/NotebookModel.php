<?php

declare(strict_types=1);

namespace App\Models;

use Medoo\Medoo;

class NotebookModel
{
    private Medoo $db;
    private string $table = 'notebooks';

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?array
    {
        return $this->db->get($this->table, '*', ['id' => $id]);
    }

    public function findByUserId(int $userId): array
    {
        return $this->db->select($this->table, '*', ['user_id' => $userId]);
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
}
