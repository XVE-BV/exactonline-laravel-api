<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\RateLimit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Exceptions\RateLimitExceededException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Models\ExactRateLimit;

class CheckRateLimitAction
{
    /**
     * Check if rate limits are exceeded for a connection
     *
     * This action checks both daily and minutely rate limits and throws
     * an exception if limits are exceeded (based on configuration).
     *
     * @param  array<string, string>|null  $headers  Optional response headers to update limits
     * @return array{
     *     daily_limit: int|null,
     *     daily_remaining: int|null,
     *     daily_reset_at: int|null,
     *     minutely_limit: int|null,
     *     minutely_remaining: int|null,
     *     minutely_reset_at: int|null,
     *     can_proceed: bool
     * }
     *
     * @throws RateLimitExceededException
     */
    public function execute(ExactConnection $connection, ?array $headers = null): array
    {
        // Get or create rate limit record
        $rateLimit = $this->getRateLimit($connection);

        // Update rate limits from headers if provided
        if ($headers !== null) {
            $rateLimit->updateFromHeaders($headers);
        }

        // Check if we have cached rate limit data
        $cachedLimits = $this->getCachedRateLimits($connection);
        if ($cachedLimits !== null) {
            $this->mergeCachedLimits($rateLimit, $cachedLimits);
        }

        // Check daily limit
        if ($rateLimit->isDailyLimitExceeded()) {
            $this->handleDailyLimitExceeded($rateLimit);
        }

        // Check minutely limit
        if ($rateLimit->isMinutelyLimitExceeded()) {
            $this->handleMinutelyLimitExceeded($rateLimit);
        }

        // Cache the current limits
        $this->cacheRateLimits($connection, $rateLimit);

        // Log if approaching limits
        $this->logLimitWarnings($rateLimit);

        return [
            'daily_limit' => $rateLimit->daily_limit,
            'daily_remaining' => $rateLimit->daily_remaining,
            'daily_reset_at' => $rateLimit->daily_reset_at,
            'minutely_limit' => $rateLimit->minutely_limit,
            'minutely_remaining' => $rateLimit->minutely_remaining,
            'minutely_reset_at' => $rateLimit->minutely_reset_at,
            'can_proceed' => true,
        ];
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
                'minutely_limit' => 60, // Default Exact Online minutely limit
                'minutely_remaining' => 60,
                'minutely_reset_at' => null,
            ]
        );
    }

    /**
     * Get cached rate limits
     *
     * @return array<string, mixed>|null
     */
    protected function getCachedRateLimits(ExactConnection $connection): ?array
    {
        $cacheKey = "exact_rate_limits:{$connection->id}";

        return Cache::get($cacheKey);
    }

    /**
     * Cache rate limits
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
            'updated_at' => now()->getTimestamp(),
        ], $ttl);
    }

    /**
     * Merge cached limits with database record
     *
     * @param  array<string, mixed>  $cachedLimits
     */
    protected function mergeCachedLimits(ExactRateLimit $rateLimit, array $cachedLimits): void
    {
        // Use the most restrictive (lowest) remaining counts
        if (isset($cachedLimits['daily_remaining']) &&
            ($rateLimit->daily_remaining === null || $cachedLimits['daily_remaining'] < $rateLimit->daily_remaining)) {
            $rateLimit->daily_remaining = $cachedLimits['daily_remaining'];
            $rateLimit->daily_reset_at = $cachedLimits['daily_reset_at'];
        }

        if (isset($cachedLimits['minutely_remaining']) &&
            ($rateLimit->minutely_remaining === null || $cachedLimits['minutely_remaining'] < $rateLimit->minutely_remaining)) {
            $rateLimit->minutely_remaining = $cachedLimits['minutely_remaining'];
            $rateLimit->minutely_reset_at = $cachedLimits['minutely_reset_at'];
        }
    }

    /**
     * Handle daily limit exceeded
     *
     * @throws RateLimitExceededException
     */
    protected function handleDailyLimitExceeded(ExactRateLimit $rateLimit): void
    {
        $shouldThrow = config('exactonline-laravel-api.rate_limiting.throw_on_daily_limit', true);

        if ($shouldThrow) {
            $resetInSeconds = $rateLimit->secondsUntilDailyReset() ?? 86400; // Default to 24 hours

            Log::error('Daily rate limit exceeded', [
                'connection_id' => $rateLimit->connection_id,
                'limit' => $rateLimit->daily_limit,
                'reset_in_seconds' => $resetInSeconds,
            ]);

            throw RateLimitExceededException::dailyLimitExceeded(
                $rateLimit->daily_limit ?? 0,
                $resetInSeconds
            );
        }

        Log::warning('Daily rate limit exceeded but configured to continue', [
            'connection_id' => $rateLimit->connection_id,
            'limit' => $rateLimit->daily_limit,
        ]);
    }

    /**
     * Handle minutely limit exceeded
     *
     * @throws RateLimitExceededException
     */
    protected function handleMinutelyLimitExceeded(ExactRateLimit $rateLimit): void
    {
        $shouldWait = config('exactonline-laravel-api.rate_limiting.wait_on_minutely_limit', true);
        $resetInSeconds = $rateLimit->secondsUntilMinutelyReset() ?? 60; // Default to 1 minute

        if (! $shouldWait) {
            Log::error('Minutely rate limit exceeded', [
                'connection_id' => $rateLimit->connection_id,
                'limit' => $rateLimit->minutely_limit,
                'reset_in_seconds' => $resetInSeconds,
            ]);

            throw RateLimitExceededException::minutelyLimitExceeded(
                $rateLimit->minutely_limit ?? 60,
                $resetInSeconds
            );
        }

        // If configured to wait, delegate to WaitForRateLimitResetAction
        Log::info('Minutely rate limit exceeded, will wait', [
            'connection_id' => $rateLimit->connection_id,
            'limit' => $rateLimit->minutely_limit,
            'reset_in_seconds' => $resetInSeconds,
        ]);
    }

    /**
     * Log warnings when approaching limits
     */
    protected function logLimitWarnings(ExactRateLimit $rateLimit): void
    {
        // Warn if approaching daily limit (90% used)
        if ($rateLimit->isApproachingDailyLimit(0.9)) {
            $percentage = $rateLimit->getDailyUsagePercentage();
            $usagePercentage = $percentage !== null ? round($percentage, 2) : null;

            Log::warning('Approaching daily rate limit', [
                'connection_id' => $rateLimit->connection_id,
                'usage_percentage' => $usagePercentage,
                'remaining' => $rateLimit->daily_remaining,
            ]);
        }

        // Warn if minutely limit is low
        if ($rateLimit->minutely_remaining !== null && $rateLimit->minutely_remaining < 10) {
            Log::warning('Low minutely rate limit', [
                'connection_id' => $rateLimit->connection_id,
                'remaining' => $rateLimit->minutely_remaining,
            ]);
        }
    }
}
