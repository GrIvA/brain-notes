<?php

declare(strict_types=1);

namespace App\Registry;

use App\Models\RegistryModel;

class TagRegistry
{
    private RegistryModel $model;
    private string $tagType;
    private int $entityId;
    private ?string $endpoint;
    private ?array $tags = null;

    public function __construct(RegistryModel $model, string $tagType, int $entityId, ?string $endpoint = null)
    {
        $this->model = $model;
        $this->tagType = $tagType;
        $this->entityId = $entityId;
        $this->endpoint = $endpoint;
    }

    /**
     * Lazy load tags from the database.
     */
    private function load(): void
    {
        if ($this->tags === null) {
            $this->tags = $this->model->fetchTags($this->tagType, $this->entityId, $this->endpoint);
        }
    }

    /**
     * Get a single tag value by key.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();
        foreach ($this->tags as $tag) {
            if ($tag['tag_key'] === $key) {
                return $this->decodeValue($tag['tag_value']);
            }
        }
        return $default;
    }

    /**
     * Get all tags for a key (e.g., multiple JWTs).
     *
     * @param string $key
     * @return array
     */
    public function getAll(string $key): array
    {
        $this->load();
        $results = [];
        foreach ($this->tags as $tag) {
            if ($tag['tag_key'] === $key) {
                $results[] = $this->decodeValue($tag['tag_value']);
            }
        }
        return $results;
    }

    /**
     * Set a tag value. Updates if key exists (for the same parent/endpoint), otherwise inserts.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $parentId
     * @param int $sortOrder
     */
    public function set(string $key, mixed $value, ?int $parentId = null, int $sortOrder = 0): void
    {
        $this->load();
        $encodedValue = $this->encodeValue($value);

        $existingId = null;
        foreach ($this->tags as $tag) {
            if ($tag['tag_key'] === $key && $tag['parent_id'] == $parentId) {
                $existingId = (int)$tag['id'];
                break;
            }
        }

        $data = [
            'tag_type' => $this->tagType,
            'entity_id' => $this->entityId,
            'endpoint' => $this->endpoint,
            'tag_key' => $key,
            'tag_value' => $encodedValue,
            'parent_id' => $parentId,
            'sort_order' => $sortOrder
        ];

        if ($existingId) {
            $data['id'] = $existingId;
        }

        $this->model->saveTag($data);
        $this->tags = null; // Reset cache to force reload on next access
    }

    /**
     * Soft delete a tag by key.
     *
     * @param string $key
     */
    public function remove(string $key): void
    {
        $this->load();
        foreach ($this->tags as $tag) {
            if ($tag['tag_key'] === $key) {
                $this->model->deleteTag((int)$tag['id']);
            }
        }
        $this->tags = null;
    }

    /**
     * Soft delete a tag by key and value.
     *
     * @param string $key
     * @param mixed $value
     */
    public function removeByKeyValue(string $key, mixed $value): void
    {
        $this->load();
        $encodedValue = $this->encodeValue($value);
        foreach ($this->tags as $tag) {
            if ($tag['tag_key'] === $key && $tag['tag_value'] === $encodedValue) {
                $this->model->deleteTag((int)$tag['id']);
            }
        }
        $this->tags = null;
    }

    /**
     * Returns tags as a hierarchical tree.
     *
     * @return array
     */
    public function tree(): array
    {
        $this->load();
        $tree = [];
        $references = [];

        foreach ($this->tags as $tag) {
            $id = $tag['id'];
            $tag['value'] = $this->decodeValue($tag['tag_value']);
            $tag['children'] = [];
            $references[$id] = $tag;

            if ($tag['parent_id'] === null) {
                $tree[] = &$references[$id];
            } else {
                if (isset($references[$tag['parent_id']])) {
                    $references[$tag['parent_id']]['children'][] = &$references[$id];
                } else {
                    $tree[] = &$references[$id]; // Fallback if parent not found in current set
                }
            }
        }

        return $tree;
    }

    private function encodeValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return (string)json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return (string)$value;
    }

    private function decodeValue(?string $value): mixed
    {
        if ($value === null) return null;
        $decoded = json_decode($value, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
    }
}
