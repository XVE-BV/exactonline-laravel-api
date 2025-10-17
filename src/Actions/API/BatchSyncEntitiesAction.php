<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Generator;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Model;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class BatchSyncEntitiesAction
{
    /**
     * Sync entities in batches using generators for memory efficiency
     *
     * This action is designed for syncing large datasets without running out of memory.
     * It uses PHP generators to process entities one at a time rather than loading
     * everything into memory.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $entityClass  The fully qualified class name of the entity (e.g., Account::class)
     * @param  callable  $processor  Callback to process each entity: function($entity): void
     * @param  array{
     *     filter?: string|null,
     *     select?: array<string>|null,
     *     orderby?: string|null,
     *     modified_after?: string|null,
     *     batch_size?: int,
     *     progress_callback?: callable|null
     * }  $options  Sync options
     * @return array{
     *     total: int,
     *     processed: int,
     *     errors: int,
     *     duration: float
     * }
     *
     * @throws ConnectionException
     */
    public function execute(
        ExactConnection $connection,
        string $entityClass,
        callable $processor,
        array $options = []
    ): array {
        // Validate entity class
        $this->validateEntityClass($entityClass);

        // Ensure we have a valid access token
        $this->ensureValidToken($connection);

        // Start timing
        $startTime = microtime(true);

        // Initialize counters
        $stats = [
            'total' => 0,
            'processed' => 0,
            'errors' => 0,
            'duration' => 0,
        ];

        try {
            // Get the picqer connection
            $picqerConnection = $connection->getPicqerConnection();

            // Create entity instance
            /** @var Model $entity */
            $entity = new $entityClass($picqerConnection);

            // Apply filters
            $this->applyFilters($entity, $options);

            // Process entities using generator
            foreach ($this->getEntitiesGenerator($connection, $entity, $options) as $index => $item) {
                $stats['total']++;

                try {
                    // Process the entity
                    $processor($item);
                    $stats['processed']++;

                    // Call progress callback if provided
                    if (isset($options['progress_callback']) && is_callable($options['progress_callback'])) {
                        $options['progress_callback']($stats['total'], $item);
                    }

                } catch (\Exception $e) {
                    $stats['errors']++;

                    Log::error('Failed to process entity in batch sync', [
                        'connection_id' => $connection->id,
                        'entity_class' => $entityClass,
                        'entity_index' => $index,
                        'error' => $e->getMessage(),
                    ]);

                    // Continue processing other entities
                    continue;
                }

                // Every 100 entities, refresh token if needed
                if ($stats['total'] % 100 === 0) {
                    $this->ensureValidToken($connection);
                }
            }

            // Calculate duration
            $stats['duration'] = round(microtime(true) - $startTime, 2);

            Log::info('Completed batch sync of entities', [
                'connection_id' => $connection->id,
                'entity_class' => $entityClass,
                'stats' => $stats,
            ]);

            return $stats;

        } catch (\Exception $e) {
            Log::error('Failed to batch sync entities', [
                'connection_id' => $connection->id,
                'entity_class' => $entityClass,
                'error' => $e->getMessage(),
                'stats' => $stats,
            ]);

            throw new ConnectionException(
                'Failed to batch sync entities: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get entities using a generator for memory efficiency
     *
     * @param  array<string, mixed>  $options
     */
    protected function getEntitiesGenerator(ExactConnection $connection, Model $entity, array $options): Generator
    {
        $batchSize = $options['batch_size'] ?? 100;
        $skip = 0;
        $hasMore = true;

        while ($hasMore) {
            // Check rate limits before each batch
            $this->checkRateLimit($connection);

            // Set pagination
            $entity->skip($skip);
            $entity->top($batchSize);

            // Get batch of entities
            $batch = $entity->get();

            // Track rate limit usage
            $this->trackRateLimitUsage($connection, $connection->getPicqerConnection());

            // Check if we have results
            if (empty($batch)) {
                $hasMore = false;
                break;
            }

            // Yield each entity
            foreach ($batch as $item) {
                yield $item;
            }

            // If we got less than batch size, we're done
            if (count($batch) < $batchSize) {
                $hasMore = false;
            }

            // Move to next batch
            $skip += $batchSize;

            // Small delay between batches to avoid hitting rate limits
            usleep(100000); // 100ms delay
        }
    }

    /**
     * Apply filters to the entity query
     *
     * @param  array<string, mixed>  $options
     */
    protected function applyFilters(Model $entity, array $options): void
    {
        $filters = [];

        // Add custom filter if provided
        if (! empty($options['filter'])) {
            $filters[] = $options['filter'];
        }

        // Filter by modified date if provided
        if (! empty($options['modified_after'])) {
            $filters[] = "Modified ge datetime'{$options['modified_after']}'";
        }

        // Apply combined filter
        if (! empty($filters)) {
            $entity->filter(implode(' and ', $filters));
        }

        // Apply field selection
        if (! empty($options['select'])) {
            $entity->select($options['select']);
        }

        // Apply ordering
        if (! empty($options['orderby'])) {
            $entity->orderBy($options['orderby']);
        } else {
            // Default to Modified ascending for incremental syncs
            $entity->orderBy('Modified');
        }
    }

    /**
     * Validate that the entity class exists and extends Model
     *
     *
     * @throws ConnectionException
     */
    protected function validateEntityClass(string $entityClass): void
    {
        if (! class_exists($entityClass)) {
            throw ConnectionException::invalidConfiguration(
                "Entity class {$entityClass} does not exist"
            );
        }

        if (! is_subclass_of($entityClass, Model::class)) {
            throw ConnectionException::invalidConfiguration(
                "Entity class {$entityClass} must extend ".Model::class
            );
        }
    }

    /**
     * Ensure the connection has a valid access token
     */
    protected function ensureValidToken(ExactConnection $connection): void
    {
        if ($this->tokenNeedsRefresh($connection)) {
            $refreshAction = Config::getAction(
                'refresh_access_token',
                RefreshAccessTokenAction::class
            );
            $refreshAction->execute($connection);

            // Refresh the connection to get updated tokens
            $connection->refresh();
        }
    }

    /**
     * Check if token needs refresh (proactive at 9 minutes)
     */
    protected function tokenNeedsRefresh(ExactConnection $connection): bool
    {
        if (empty($connection->token_expires_at)) {
            return true;
        }

        // Refresh proactively at 9 minutes (540 seconds before expiry)
        return $connection->token_expires_at < (now()->timestamp + 540);
    }

    /**
     * Check rate limits before making the API request
     */
    protected function checkRateLimit(ExactConnection $connection): void
    {
        $checkRateLimitAction = Config::getAction(
            'check_rate_limit',
            CheckRateLimitAction::class
        );
        $checkRateLimitAction->execute($connection);
    }

    /**
     * Track rate limit usage after the API request
     */
    protected function trackRateLimitUsage(ExactConnection $connection, \Picqer\Financials\Exact\Connection $picqerConnection): void
    {
        $trackRateLimitAction = Config::getAction(
            'track_rate_limit_usage',
            TrackRateLimitUsageAction::class
        );
        $trackRateLimitAction->execute($connection, $picqerConnection);
    }
}
