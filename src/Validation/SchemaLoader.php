<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Validation;

use RuntimeException;

class SchemaLoader
{
    protected array $cache = [];

    protected ?string $customPath = null;

    public function __construct(?string $customPath = null)
    {
        $this->customPath = $customPath;
    }

    public function load(string $entity): array
    {
        if (! isset($this->cache[$entity])) {
            $path = $this->getSchemaPath($entity);

            if (! file_exists($path)) {
                throw new RuntimeException("Schema not found for entity: {$entity}. Expected at: {$path}");
            }

            $content = file_get_contents($path);
            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException("Invalid JSON in schema file for {$entity}: ".json_last_error_msg());
            }

            $this->cache[$entity] = $decoded;
        }

        return $this->cache[$entity];
    }

    public function getFields(string $entity): array
    {
        return $this->load($entity)['fields'] ?? [];
    }

    public function getEndpoint(string $entity): ?string
    {
        return $this->load($entity)['endpoint'] ?? null;
    }

    public function hasSchema(string $entity): bool
    {
        return file_exists($this->getSchemaPath($entity));
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }

    protected function getSchemaPath(string $entity): string
    {
        $basePath = $this->customPath ?? dirname(__DIR__, 2).'/resources/schemas';

        return $basePath.'/'.$entity.'.json';
    }
}
