<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Unit;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class GetUnitAction
{
    /**
     * Retrieve a single unit from Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $unitId  The unit ID (GUID)
     * @param  array{
     *     select?: array<string>|null
     * }  $options  OData query options
     * @return array<string, mixed>|null Returns unit data or null if not found
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $unitId, array $options = []): ?array
    {
        $this->ensureValidToken($connection);

        $this->checkRateLimit($connection);

        try {
            $picqerConnection = $connection->getPicqerConnection();

            $unit = new Unit($picqerConnection);

            if (! empty($options['select'])) {
                $unit->select($options['select']);
            }

            $result = $unit->find($unitId);

            $this->trackRateLimitUsage($connection, $picqerConnection);

            if ($result === null) {
                Log::info('Unit not found in Exact Online', [
                    'connection_id' => $connection->id,
                    'unit_id' => $unitId,
                ]);

                return null;
            }

            Log::info('Retrieved unit from Exact Online', [
                'connection_id' => $connection->id,
                'unit_id' => $unitId,
                'unit_code' => $result->Code ?? 'N/A',
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve unit from Exact Online', [
                'connection_id' => $connection->id,
                'unit_id' => $unitId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve unit: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
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
    protected function trackRateLimitUsage(ExactConnection $connection, \Picqer\Financials\Exact\Connection $picqerConnection): void
    {
        $trackRateLimitAction = Config::getAction(
            'track_rate_limit_usage',
            TrackRateLimitUsageAction::class
        );
        $trackRateLimitAction->execute($connection, $picqerConnection);
    }
}
