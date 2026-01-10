<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Payment;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class GetPaymentsAction
{
    /**
     * Retrieve payments from Exact Online
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array{
     *     filter?: string|null,
     *     select?: array<string>|null,
     *     expand?: array<string>|null,
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
        // Ensure we have a valid access token
        $this->ensureValidToken($connection);

        // Check rate limits before making the request
        $this->checkRateLimit($connection);

        try {
            // Get the picqer connection
            $picqerConnection = $connection->getPicqerConnection();

            // Create Payment instance
            $payment = new Payment($picqerConnection);

            // Apply filters if provided
            $this->applyQueryOptions($payment, $options);

            // Get payments
            $payments = $payment->get();

            // Track rate limit usage after the request
            $this->trackRateLimitUsage($connection, $picqerConnection);

            Log::info('Retrieved payments from Exact Online', [
                'connection_id' => $connection->id,
                'count' => count($payments),
                'options' => $options,
            ]);

            // Convert to Laravel collection for easier manipulation
            return collect($payments)->map(function ($payment) {
                return $payment->attributes();
            });

        } catch (\Exception $e) {
            Log::error('Failed to retrieve payments from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'options' => $options,
            ]);

            throw new ConnectionException(
                'Failed to retrieve payments: '.$e->getMessage(),
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

    /**
     * Apply OData query options to the entity
     *
     * @param  array<string, mixed>  $options
     */
    protected function applyQueryOptions(Payment $payment, array $options): void
    {
        // Apply filter
        if (! empty($options['filter'])) {
            $payment->filter($options['filter']);
        }

        // Apply select (field selection)
        if (! empty($options['select'])) {
            $payment->select($options['select']);
        }

        // Apply expand (related entities)
        if (! empty($options['expand'])) {
            $payment->expand(implode(',', $options['expand']));
        }

        // Apply orderby
        if (! empty($options['orderby'])) {
            $payment->orderBy($options['orderby']);
        }

        // Apply top (limit)
        if (! empty($options['top'])) {
            $payment->top($options['top']);
        }

        // Apply skip (offset)
        if (! empty($options['skip'])) {
            $payment->skip($options['skip']);
        }
    }
}
