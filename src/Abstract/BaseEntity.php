<?php

declare(strict_types=1);

namespace App\Abstract;

use App\Registry\TagRegistry;
use App\Models\RegistryModel;

abstract class BaseEntity
{
    protected int $id;
    protected RegistryModel $registryModel;
    protected string $tagType;
    protected array $tagInstances = [];

    public function __construct(int $id, RegistryModel $registryModel)
    {
        $this->id = $id;
        $this->registryModel = $registryModel;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the tag registry for this entity.
     *
     * @param string|null $endpoint
     * @return TagRegistry
     */
    public function tags(?string $endpoint = null): TagRegistry
    {
        $key = $endpoint ?? 'default';
        if (!isset($this->tagInstances[$key])) {
            $this->tagInstances[$key] = new TagRegistry(
                $this->registryModel,
                $this->tagType,
                $this->id,
                $endpoint
            );
        }
        return $this->tagInstances[$key];
    }
}
