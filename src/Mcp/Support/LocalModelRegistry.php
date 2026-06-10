<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Mcp\Support;

use Illuminate\Database\Eloquent\Model;
use XVE\ExactonlineLaravelApi\Models\ExactDivision;
use XVE\ExactonlineLaravelApi\Models\ExactMapping;
use XVE\ExactonlineLaravelApi\Models\ExactRateLimit;
use XVE\ExactonlineLaravelApi\Models\ExactWebhook;

/**
 * Maps record_type strings to their Eloquent model classes and filterable columns.
 */
class LocalModelRegistry
{
    /**
     * Supported record types with their model class and allowed filter columns.
     *
     * @var array<string, array{model: class-string<Model>, filters: array<string>}>
     */
    private const TYPES = [
        'mappings' => [
            'model' => ExactMapping::class,
            'filters' => ['connection_id', 'exact_id', 'reference_type', 'environment', 'mappable_type', 'division'],
        ],
        'rate_limits' => [
            'model' => ExactRateLimit::class,
            'filters' => ['connection_id'],
        ],
        'webhooks' => [
            'model' => ExactWebhook::class,
            'filters' => ['connection_id', 'topic', 'is_active'],
        ],
        'divisions' => [
            'model' => ExactDivision::class,
            'filters' => ['connection_id', 'code', 'is_main', 'status'],
        ],
    ];

    /**
     * @return array<string>
     */
    public function types(): array
    {
        return array_keys(self::TYPES);
    }

    /**
     * @return class-string<Model>
     *
     * @throws \InvalidArgumentException
     */
    public function modelClass(string $type): string
    {
        if (! isset(self::TYPES[$type])) {
            throw new \InvalidArgumentException(
                "Unknown record_type \"{$type}\". Supported: ".implode(', ', $this->types()).'.'
            );
        }

        return self::TYPES[$type]['model'];
    }

    /**
     * @return array<string>
     */
    public function filterableColumns(string $type): array
    {
        return self::TYPES[$type]['filters'] ?? [];
    }
}
