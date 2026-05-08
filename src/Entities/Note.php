<?php

declare(strict_types=1);

namespace App\Entities;

use App\Abstract\BaseEntity;
use App\Models\RegistryModel;

class Note extends BaseEntity
{
    protected string $tagType = 'note';
    private int $sectionId;
    private ?int $userId;
    private string $title;
    private string $content;
    private int $attributes;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(array $data, RegistryModel $registryModel)
    {
        parent::__construct((int)$data['id'], $registryModel);
        $this->sectionId = (int)$data['section_id'];
        $this->userId = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $this->title = $data['title'];
        $this->content = $data['content'];
        $this->attributes = (int)$data['attributes'];
        $this->createdAt = $data['created_at'];
        $this->updatedAt = $data['updated_at'];
    }

    public function getSectionId(): int
    {
        return $this->sectionId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Returns protected content. 
     * If note is encrypted, returns an empty string to prevent leakage in lists/API.
     */
    public function getContent(): string
    {
        if ($this->isEncrypted()) {
            return '';
        }
        return $this->content;
    }

    /**
     * Returns raw content from DB, regardless of encryption status.
     * Use ONLY for decryption purposes in controllers/services.
     */
    public function getRawContent(): string
    {
        return $this->content;
    }

    public function getAttributes(): int
    {
        return $this->attributes;
    }

    public function isPublic(): bool
    {
        return ($this->attributes & 1) === 1;
    }

    public function isPinned(): bool
    {
        return ($this->attributes & 2) === 2;
    }

    public function isDraft(): bool
    {
        return ($this->attributes & 4) === 4;
    }

    public function isEncrypted(): bool
    {
        return ($this->attributes & 8) === 8;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->sectionId,
            'user_id' => $this->userId,
            'title' => $this->title,
            'content' => $this->getContent(),
            'attributes' => $this->attributes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
