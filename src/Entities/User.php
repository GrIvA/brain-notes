<?php

declare(strict_types=1);

namespace App\Entities;

use App\Abstract\BaseEntity;
use App\Enums\UserRole;
use App\Models\RegistryModel;

class User extends BaseEntity
{
    protected string $tagType = 'user';
    private string $email;
    private string $passwordHash;
    private int $rolesMask;
    private ?string $name;

    public function __construct(array $data, RegistryModel $registryModel)
    {
        parent::__construct((int)$data['id'], $registryModel);
        $this->email = $data['email'];
        $this->passwordHash = $data['password_hash'];
        $this->rolesMask = (int)$data['roles_mask'];
        $this->name = $data['name'] ?? null;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Verify if the provided password matches the stored hash.
     *
     * @param string $password
     * @return bool
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    /**
     * Check if the user has a specific role using bitmask.
     *
     * @param UserRole $role
     * @return bool
     */
    public function hasRole(UserRole $role): bool
    {
        return ($this->rolesMask & $role->value) === $role->value;
    }

    /**
     * Get all roles for the user.
     *
     * @return UserRole[]
     */
    public function getRoles(): array
    {
        $roles = [];
        foreach (UserRole::cases() as $role) {
            if ($this->hasRole($role)) {
                $roles[] = $role;
            }
        }
        return $roles;
    }

    /**
     * Convert entity to array (useful for API responses).
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'roles' => array_map(fn($r) => strtolower($r->name), $this->getRoles()),
        ];
    }
}
