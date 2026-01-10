<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Contracts;

use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

/**
 * Interface for models that can be synced to Exact Online.
 */
interface SyncsWithExact extends HasExactMapping
{
    /**
     * Convert this model to an array suitable for Exact Online API.
     *
     * @return array<string, mixed>
     */
    public function toExactArray(ExactConnection $connection): array;

    /**
     * Get the Exact Online API endpoint for this model type.
     * Example: 'crm/Accounts' or 'logistics/Items'
     */
    public function getExactEndpoint(): string;

    /**
     * Determine if this model should be synced to Exact Online.
     */
    public function shouldSyncToExact(ExactConnection $connection): bool;
}
