<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Models\ExactMapping;

/**
 * Interface for models that can be mapped to Exact Online entities.
 */
interface HasExactMapping
{
    /**
     * Get all Exact mappings for this model.
     *
     * @return MorphMany<ExactMapping, $this>
     */
    public function exactMappings(): MorphMany;

    /**
     * Get the Exact ID for a specific connection and reference type.
     */
    public function getExactId(ExactConnection $connection, string $type = 'primary'): ?string;

    /**
     * Get the Exact code for a specific connection and reference type.
     */
    public function getExactCode(ExactConnection $connection, string $type = 'primary'): ?string;

    /**
     * Check if this model has an Exact ID for a specific connection and reference type.
     */
    public function hasExactId(ExactConnection $connection, string $type = 'primary'): bool;

    /**
     * Set the Exact ID for a specific connection and reference type.
     */
    public function setExactId(
        ExactConnection $connection,
        string $exactId,
        string $type = 'primary',
        ?string $exactCode = null
    ): void;

    /**
     * Remove the Exact ID for a specific connection and reference type.
     */
    public function removeExactId(ExactConnection $connection, string $type = 'primary'): void;

    /**
     * Get the Exact mapping for a specific connection and reference type.
     */
    public function getExactMapping(ExactConnection $connection, string $type = 'primary'): ?ExactMapping;

    /**
     * Find a model by its Exact ID.
     */
    public static function findByExactId(
        string $exactId,
        ?ExactConnection $connection = null,
        string $type = 'primary'
    ): ?static;
}
