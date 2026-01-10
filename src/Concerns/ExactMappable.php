<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Models\ExactMapping;
use Skylence\ExactonlineLaravelApi\Support\Config;

/**
 * Trait for Eloquent models that can be mapped to Exact Online entities.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait ExactMappable
{
    /**
     * Boot the trait.
     */
    public static function bootExactMappable(): void
    {
        // Auto-delete mappings when the model is deleted
        static::deleting(function ($model): void {
            $model->exactMappings()->delete();
        });
    }

    /**
     * Get all Exact mappings for this model.
     *
     * @return MorphMany<ExactMapping, $this>
     */
    public function exactMappings(): MorphMany
    {
        return $this->morphMany(ExactMapping::class, 'mappable');
    }

    /**
     * Get the Exact ID for a specific connection and reference type.
     */
    public function getExactId(ExactConnection $connection, string $type = 'primary'): ?string
    {
        return $this->getExactMapping($connection, $type)?->exact_id;
    }

    /**
     * Get the Exact code for a specific connection and reference type.
     */
    public function getExactCode(ExactConnection $connection, string $type = 'primary'): ?string
    {
        return $this->getExactMapping($connection, $type)?->exact_code;
    }

    /**
     * Get the Exact mapping for a specific connection and reference type.
     */
    public function getExactMapping(ExactConnection $connection, string $type = 'primary'): ?ExactMapping
    {
        return $this->exactMappings()
            ->forConnection($connection)
            ->currentEnvironment()
            ->ofType($type)
            ->first();
    }

    /**
     * Check if this model has an Exact ID for a specific connection and reference type.
     */
    public function hasExactId(ExactConnection $connection, string $type = 'primary'): bool
    {
        return $this->getExactId($connection, $type) !== null;
    }

    /**
     * Set the Exact ID for a specific connection and reference type.
     */
    public function setExactId(
        ExactConnection $connection,
        string $exactId,
        string $type = 'primary',
        ?string $exactCode = null
    ): void {
        $this->exactMappings()->updateOrCreate(
            [
                'connection_id' => $connection->id,
                'environment' => Config::getMappingEnvironment(),
                'reference_type' => $type,
            ],
            [
                'division' => $connection->division,
                'exact_id' => $exactId,
                'exact_code' => $exactCode,
                'synced_at' => now(),
                'last_error' => null,
            ]
        );
    }

    /**
     * Remove the Exact ID for a specific connection and reference type.
     */
    public function removeExactId(ExactConnection $connection, string $type = 'primary'): void
    {
        $this->exactMappings()
            ->forConnection($connection)
            ->currentEnvironment()
            ->ofType($type)
            ->delete();
    }

    /**
     * Get all Exact IDs for a specific connection.
     *
     * @return array<string, string> Array of [reference_type => exact_id]
     */
    public function getAllExactIds(ExactConnection $connection): array
    {
        return $this->exactMappings()
            ->forConnection($connection)
            ->currentEnvironment()
            ->pluck('exact_id', 'reference_type')
            ->toArray();
    }

    /**
     * Find a model by its Exact ID.
     */
    public static function findByExactId(
        string $exactId,
        ?ExactConnection $connection = null,
        string $type = 'primary'
    ): ?static {
        $query = ExactMapping::query()
            ->where('mappable_type', static::class)
            ->forExactId($exactId)
            ->currentEnvironment()
            ->ofType($type);

        if ($connection) {
            $query->forConnection($connection);
        }

        $mapping = $query->first();

        if (! $mapping) {
            return null;
        }

        return static::find($mapping->mappable_id);
    }

    /**
     * Mark the Exact mapping as synced.
     */
    public function markExactSynced(ExactConnection $connection, string $type = 'primary'): void
    {
        $this->getExactMapping($connection, $type)?->markAsSynced();
    }

    /**
     * Record an error on the Exact mapping.
     */
    public function recordExactError(ExactConnection $connection, string $error, string $type = 'primary'): void
    {
        $mapping = $this->getExactMapping($connection, $type);

        if ($mapping) {
            $mapping->recordError($error);
        }
    }

    /**
     * Get the Exact Online URL for this model.
     */
    public function getExactUrl(ExactConnection $connection, string $type = 'primary'): ?string
    {
        return $this->getExactMapping($connection, $type)?->getExactUrl();
    }

    /**
     * Get all Exact Online URLs for this model across all connections.
     *
     * @return array<string, string|null> Array of [reference_type => url]
     */
    public function getExactUrls(string $type = 'primary'): array
    {
        return $this->exactMappings()
            ->currentEnvironment()
            ->ofType($type)
            ->with('connection')
            ->get()
            ->mapWithKeys(fn (ExactMapping $mapping) => [
                $mapping->connection->name ?? "connection_{$mapping->connection_id}" => $mapping->getExactUrl(),
            ])
            ->toArray();
    }

    /**
     * Check if this model was synced to Exact recently.
     */
    public function wasExactSyncedRecently(ExactConnection $connection, string $type = 'primary', int $minutes = 60): bool
    {
        return $this->getExactMapping($connection, $type)?->wasSyncedRecently($minutes) ?? false;
    }

    /**
     * Check if this model has an Exact sync error.
     */
    public function hasExactError(ExactConnection $connection, string $type = 'primary'): bool
    {
        return $this->getExactMapping($connection, $type)?->hasError() ?? false;
    }

    /**
     * Get the last Exact sync error.
     */
    public function getExactError(ExactConnection $connection, string $type = 'primary'): ?string
    {
        return $this->getExactMapping($connection, $type)?->last_error;
    }
}
