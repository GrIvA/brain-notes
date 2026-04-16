<?php

declare(strict_types=1);

namespace App\Models;

use Medoo\Medoo;

class UserModel
{
    private Medoo $db;
    private string $table = 'users';

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    /**
     * Find a user by email.
     *
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        return $this->db->get($this->table, '*', ['email' => $email]);
    }

    /**
     * Find a user by ID.
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        return $this->db->get($this->table, '*', ['id' => $id]);
    }

    /**
     * Create a new user.
     *
     * @param array $data
     * @return int|string|null
     */
    public function create(array $data): int|string|null
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->id();
    }

    /**
     * Update an existing user.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $result = $this->db->update($this->table, $data, ['id' => $id]);
        return $result->rowCount() > 0;
    }
}
