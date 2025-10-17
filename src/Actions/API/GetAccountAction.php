<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Account;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class GetAccountAction
{
    /**
     * Retrieve a single account from Exact Online
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $accountId  The account ID (GUID)
     * @param  array{
     *     select?: array<string>|null,
     *     expand?: array<string>|null
     * }  $options  OData query options
     * @return array<string, mixed>|null Returns account data or null if not found
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $accountId, array $options = []): ?array
    {
        // Ensure we have a valid access token
        $this->ensureValidToken($connection);

        // Check rate limits before making the request
        $this->checkRateLimit($connection);

        try {
            // Get the picqer connection
            $picqerConnection = $connection->getPicqerConnection();

            // Create Account instance
            $account = new Account($picqerConnection);

            // Apply field selection if provided
            if (! empty($options['select'])) {
                $account->select($options['select']);
            }

            // Apply expand if provided
            if (! empty($options['expand'])) {
                $account->expand(implode(',', $options['expand']));
            }

            // Find the account by ID
            $result = $account->find($accountId);

            // Track rate limit usage after the request
            $this->trackRateLimitUsage($connection, $picqerConnection);

            if ($result === null) {
                Log::info('Account not found in Exact Online', [
                    'connection_id' => $connection->id,
                    'account_id' => $accountId,
                ]);

                return null;
            }

            Log::info('Retrieved account from Exact Online', [
                'connection_id' => $connection->id,
                'account_id' => $accountId,
                'account_name' => $result->Name ?? 'N/A',
            ]);

            // Return attributes as array
            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve account from Exact Online', [
                'connection_id' => $connection->id,
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve account: ' . $e->getMessage(),
                $e->getCode(),
                $e
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
