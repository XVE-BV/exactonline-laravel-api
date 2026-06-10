<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\Unit;
use XVE\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use XVE\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use XVE\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;
use XVE\ExactonlineLaravelApi\Support\Config;

class GetUnitsAction
{
    /**
     * Retrieve units from Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array{
     *     filter?: string|null,
     *     select?: array<string>|null,
     *     orderby?: string|null,
     *     top?: int|null,
     *     skip?: int|null
     * }  $options  OData query options
     * @return Collection<int, array<string, mixed>>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $options = []): Collection
    {
        $this->ensureValidToken($connection);

        $this->checkRateLimit($connection);

        try {
            $picqerConnection = $connection->getPicqerConnection();

            $unit = new Unit($picqerConnection);

            $queryOptions = $this->buildQueryOptions($options);

            $units = $unit->get($queryOptions);

            $this->trackRateLimitUsage($connection, $picqerConnection);

            Log::info('Retrieved units from Exact Online', [
                'connection_id' => $connection->id,
                'count' => count($units),
                'options' => $options,
            ]);

            return collect($units)->map(function ($unit) {
                return $unit->attributes();
            });

        } catch (\Exception $e) {
            Log::error('Failed to retrieve units from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'options' => $options,
            ]);

            throw new ConnectionException(
                'Failed to retrieve units: '.$e->getMessage(),
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

        return $connection->token_expires_at < (now()->getTimestamp() + 540);
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

    /**
     * Build OData query options for Picqer's request API.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function buildQueryOptions(array $options): array
    {
        $queryOptions = [];

        if (! empty($options['filter'])) {
            $queryOptions['$filter'] = $options['filter'];
        }

        if (! empty($options['select']) && is_array($options['select'])) {
            $queryOptions['$select'] = implode(',', $options['select']);
        }

        if (! empty($options['expand']) && is_array($options['expand'])) {
            $queryOptions['$expand'] = implode(',', $options['expand']);
        }

        if (! empty($options['orderby'])) {
            $queryOptions['$orderby'] = $options['orderby'];
        }

        if (isset($options['top'])) {
            $queryOptions['$top'] = (int) $options['top'];
        }

        if (isset($options['skip'])) {
            $queryOptions['$skip'] = (int) $options['skip'];
        }

        return $queryOptions;
    }
}
