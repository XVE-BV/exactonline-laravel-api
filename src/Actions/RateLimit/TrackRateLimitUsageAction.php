<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\RateLimit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Events\RateLimitApproaching;
use Skylence\ExactonlineLaravelApi\Events\RateLimitUpdated;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Models\ExactRateLimit;

class TrackRateLimitUsageAction
{
    /**
     * Track rate limit usage from API response headers
     *
     * This action parses rate limit headers from Exact Online API responses
     * and updates the rate limit tracking for the connection.
     *
     * @param  array<string, string>  $headers  Response headers from Exact Online
     * @return array{
     *     tracked: bool,
     *     daily_usage: float|null,
     *     minutely_usage: float|null,
     *     warnings: array<string>
     * }
     */
    public function execute(ExactConnection $connection, array $headers): array
    {
        $result = [
            'tracked' => false,
            'daily_usage' => null,
            'minutely_usage' => null,
            'warnings' => [],
        ];

        // Parse rate limit headers
        $rateLimits = $this->parseRateLimitHeaders($headers);

        if (empty($rateLimits)) {
            Log::debug('No rate limit headers found in response', [
                'connection_id' => $connection->id,
            ]);

            return $result;
        }

        // Get or create rate limit record
        $rateLimit = $this->getRateLimit($connection);

        // Update rate limit record
        $this->updateRateLimitRecord($rateLimit, $rateLimits);

        // Calculate usage percentages
        if (isset($rateLimits['daily_limit']) && isset($rateLimits['daily_remaining'])) {
            $result['daily_usage'] = $this->calculateUsagePercentage(
                $rateLimits['daily_limit'],
                $rateLimits['daily_remaining']
            );
        }

        if (isset($rateLimits['minutely_limit']) && isset($rateLimits['minutely_remaining'])) {
            $result['minutely_usage'] = $this->calculateUsagePercentage(
                $rateLimits['minutely_limit'],
                $rateLimits['minutely_remaining']
            );
        }

        // Check for warnings
        $result['warnings'] = $this->checkForWarnings($rateLimit, $result);

        // Cache the updated limits
        $this->cacheRateLimits($connection, $rateLimit);

        // Dispatch events
        $this->dispatchEvents($connection, $rateLimit, $result);

        // Increment call counter
        $rateLimit->incrementCallCounter();

        $result['tracked'] = true;

        Log::debug('Rate limit usage tracked', [
            'connection_id' => $connection->id,
            'daily_usage' => $result['daily_usage'],
            'minutely_usage' => $result['minutely_usage'],
        ]);

        return $result;
    }

    /**
     * Parse rate limit headers from Exact Online response
     *
     * @param  array<string, string>  $headers
     * @return array<string, int>
     */
    protected function parseRateLimitHeaders(array $headers): array
    {
        $rateLimits = [];

        // Exact Online rate limit headers (case-insensitive lookup)
        $headerMap = [
            'x-ratelimit-limit' => 'daily_limit',
            'x-ratelimit-remaining' => 'daily_remaining',
            'x-ratelimit-reset' => 'daily_reset_at',
            'x-ratelimit-minutely-limit' => 'minutely_limit',
            'x-ratelimit-minutely-remaining' => 'minutely_remaining',
            'x-ratelimit-minutely-reset' => 'minutely_reset_at',
        ];

        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);

            if (isset($headerMap[$lowerKey])) {
                $field = $headerMap[$lowerKey];

                // Reset times are in milliseconds, convert to seconds
                if (str_contains($field, 'reset_at')) {
                    $rateLimits[$field] = (int) ($value / 1000);
                } else {
                    $rateLimits[$field] = (int) $value;
                }
            }
        }

        return $rateLimits;
    }

    /**
     * Get or create rate limit record for connection
     */
    protected function getRateLimit(ExactConnection $connection): ExactRateLimit
    {
        return $connection->rateLimit()->firstOrCreate(
            ['connection_id' => $connection->id],
            [
                'daily_limit' => null,
                'daily_remaining' => null,
                'daily_reset_at' => null,
                'minutely_limit' => 60,
                'minutely_remaining' => 60,
                'minutely_reset_at' => null,
            ]
        );
    }

    /**
     * Update rate limit record with parsed headers
     *
     * @param  array<string, int>  $rateLimits
     */
    protected function updateRateLimitRecord(ExactRateLimit $rateLimit, array $rateLimits): void
    {
        $updates = [
            'last_checked_at' => now(),
        ];

        foreach ($rateLimits as $field => $value) {
            $updates[$field] = $value;
        }

        $rateLimit->update($updates);
    }

    /**
     * Calculate usage percentage
     */
    protected function calculateUsagePercentage(int $limit, int $remaining): float
    {
        if ($limit === 0) {
            return 0.0;
        }

        $used = $limit - $remaining;

        return ($used / $limit) * 100;
    }

    /**
     * Check for rate limit warnings
     *
     * @param  array<string, mixed>  $result
     * @return array<string>
     */
    protected function checkForWarnings(ExactRateLimit $rateLimit, array $result): array
    {
        $warnings = [];

        // Check daily limit
        if ($result['daily_usage'] !== null) {
            if ($result['daily_usage'] >= 90) {
                $warnings[] = sprintf(
                    'Daily rate limit is at %.1f%% (%.0f of %d requests used)',
                    $result['daily_usage'],
                    ($rateLimit->daily_limit - $rateLimit->daily_remaining),
                    $rateLimit->daily_limit
                );
            } elseif ($result['daily_usage'] >= 75) {
                $warnings[] = sprintf(
                    'Approaching daily rate limit: %.1f%% used',
                    $result['daily_usage']
                );
            }
        }

        // Check minutely limit
        if ($result['minutely_usage'] !== null) {
            if ($result['minutely_usage'] >= 80) {
                $warnings[] = sprintf(
                    'Minutely rate limit is at %.1f%% (%d of %d requests used)',
                    $result['minutely_usage'],
                    ($rateLimit->minutely_limit - $rateLimit->minutely_remaining),
                    $rateLimit->minutely_limit
                );
            }
        }

        // Check if limits are very low
        if ($rateLimit->daily_remaining !== null && $rateLimit->daily_remaining < 100) {
            $warnings[] = sprintf('Only %d daily requests remaining', $rateLimit->daily_remaining);
        }

        if ($rateLimit->minutely_remaining !== null && $rateLimit->minutely_remaining < 10) {
            $warnings[] = sprintf('Only %d minutely requests remaining', $rateLimit->minutely_remaining);
        }

        return $warnings;
    }

    /**
     * Cache rate limits for quick access
     */
    protected function cacheRateLimits(ExactConnection $connection, ExactRateLimit $rateLimit): void
    {
        $cacheKey = "exact_rate_limits:{$connection->id}";
        $ttl = 60; // Cache for 1 minute

        Cache::put($cacheKey, [
            'daily_limit' => $rateLimit->daily_limit,
            'daily_remaining' => $rateLimit->daily_remaining,
            'daily_reset_at' => $rateLimit->daily_reset_at,
            'minutely_limit' => $rateLimit->minutely_limit,
            'minutely_remaining' => $rateLimit->minutely_remaining,
            'minutely_reset_at' => $rateLimit->minutely_reset_at,
            'updated_at' => now()->timestamp,
        ], $ttl);
    }

    /**
     * Dispatch relevant events
     *
     * @param  array<string, mixed>  $result
     */
    protected function dispatchEvents(ExactConnection $connection, ExactRateLimit $rateLimit, array $result): void
    {
        // Dispatch general update event
        event(new RateLimitUpdated($connection, $rateLimit));

        // Dispatch warning event if approaching limits
        if (! empty($result['warnings'])) {
            event(new RateLimitApproaching($connection, $rateLimit, $result['warnings']));
        }

        // Log warnings
        foreach ($result['warnings'] as $warning) {
            Log::warning('Rate limit warning', [
                'connection_id' => $connection->id,
                'warning' => $warning,
            ]);
        }
    }
}
