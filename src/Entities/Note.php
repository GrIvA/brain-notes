<?php

declare(strict_types=1);

namespace App\Entities;

use App\Abstract\BaseEntity;
use App\Models\RegistryModel;

class Note extends BaseEntity
{
    protected string $tagType = 'note';
    private int $sectionId;
    private string $title;
    private string $content;
    private int $attributes;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(array $data, RegistryModel $registryModel)
    {
        parent::__construct((int)$data['id'], $registryModel);
        $this->sectionId = (int)$data['section_id'];
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->sectionId,
            'title' => $this->title,
            'content' => $this->content,
            'attributes' => $this->attributes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
