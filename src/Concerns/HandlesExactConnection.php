<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Concerns;

use Picqer\Financials\Exact\Connection;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

/**
 * Shared functionality for actions that interact with Exact Online.
 *
 * Provides:
 * - Token refresh management
 * - Rate limit checking and tracking
 * - Connection preparation
 */
trait HandlesExactConnection
{
    /**
     * Prepare connection for API request.
     * Ensures valid token and checks rate limits.
     */
    protected function prepareConnection(ExactConnection $connection): Connection
    {
        $this->ensureValidToken($connection);
        $this->checkRateLimit($connection);

        return $connection->getPicqerConnection();
    }

    /**
     * Complete request by tracking rate limit usage.
     */
    protected function completeRequest(ExactConnection $connection, Connection $picqerConnection): void
    {
        $this->trackRateLimitUsage($connection, $picqerConnection);
        $connection->markAsUsed();
    }

    /**
     * Ensure the connection has a valid access token.
     */
    protected function ensureValidToken(ExactConnection $connection): void
    {
        if ($this->tokenNeedsRefresh($connection)) {
            $refreshAction = Config::getAction(
                'refresh_access_token',
                RefreshAccessTokenAction::class
            );
            $refreshAction->execute($connection);

            // Refresh the model to get updated tokens
            $connection->refresh();
        }
    }

    /**
     * Check if token needs refresh (proactive at 9 minutes).
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
     * Check rate limits before making the API request.
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
     * Track rate limit usage after the API request.
     */
    protected function trackRateLimitUsage(ExactConnection $connection, Connection $picqerConnection): void
    {
        $trackRateLimitAction = Config::getAction(
            'track_rate_limit_usage',
            TrackRateLimitUsageAction::class
        );
        $trackRateLimitAction->execute($connection, $picqerConnection);
    }
}
