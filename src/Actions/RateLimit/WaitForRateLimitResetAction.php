<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\RateLimit;

use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Exceptions\RateLimitExceededException;
use Skylence\ExactonlineLaravelApi\Models\ExactRateLimit;

class WaitForRateLimitResetAction
{
    /**
     * Wait for rate limit to reset
     *
     * This action sleeps until the rate limit resets, primarily used for
     * minutely limits. Daily limits should typically throw an exception
     * rather than waiting (as configured).
     *
     * @param  string  $limitType  'daily' or 'minutely'
     *
     * @throws RateLimitExceededException
     */
    public function execute(ExactRateLimit $rateLimit, string $limitType = 'minutely'): void
    {
        $maxWaitSeconds = config('exactonline-laravel-api.rate_limiting.max_wait_seconds', 65);

        if ($limitType === 'daily') {
            $this->handleDailyLimit($rateLimit, $maxWaitSeconds);
        } else {
            $this->handleMinutelyLimit($rateLimit, $maxWaitSeconds);
        }
    }

    /**
     * Handle daily rate limit
     *
     * @throws RateLimitExceededException
     */
    protected function handleDailyLimit(ExactRateLimit $rateLimit, int $maxWaitSeconds): void
    {
        $secondsUntilReset = $rateLimit->secondsUntilDailyReset() ?? 86400;

        // Daily limits typically reset after 24 hours, so we shouldn't wait
        if ($secondsUntilReset > $maxWaitSeconds) {
            Log::error('Daily rate limit reset time exceeds maximum wait time', [
                'connection_id' => $rateLimit->connection_id,
                'seconds_until_reset' => $secondsUntilReset,
                'max_wait_seconds' => $maxWaitSeconds,
            ]);

            throw RateLimitExceededException::dailyLimitExceeded(
                $rateLimit->daily_limit ?? 0,
                $secondsUntilReset
            );
        }

        // If somehow the daily limit resets soon, we can wait
        $this->waitForReset($rateLimit, $secondsUntilReset, 'daily');
    }

    /**
     * Handle minutely rate limit
     *
     * @throws RateLimitExceededException
     */
    protected function handleMinutelyLimit(ExactRateLimit $rateLimit, int $maxWaitSeconds): void
    {
        $secondsUntilReset = $rateLimit->secondsUntilMinutelyReset() ?? 60;

        if ($secondsUntilReset > $maxWaitSeconds) {
            Log::error('Minutely rate limit reset time exceeds maximum wait time', [
                'connection_id' => $rateLimit->connection_id,
                'seconds_until_reset' => $secondsUntilReset,
                'max_wait_seconds' => $maxWaitSeconds,
            ]);

            throw RateLimitExceededException::minutelyLimitExceeded(
                $rateLimit->minutely_limit ?? 60,
                $secondsUntilReset
            );
        }

        $this->waitForReset($rateLimit, $secondsUntilReset, 'minutely');
    }

    /**
     * Wait for rate limit to reset
     */
    protected function waitForReset(ExactRateLimit $rateLimit, int $secondsToWait, string $limitType): void
    {
        // Add a small buffer to ensure the limit has actually reset
        $secondsToWait = min($secondsToWait + 1, 65);

        Log::info('Waiting for rate limit to reset', [
            'connection_id' => $rateLimit->connection_id,
            'limit_type' => $limitType,
            'seconds_to_wait' => $secondsToWait,
        ]);

        // For longer waits, sleep in chunks to allow for graceful shutdown
        if ($secondsToWait > 10) {
            $chunks = (int) ceil($secondsToWait / 10);
            $remainingSeconds = $secondsToWait;

            for ($i = 0; $i < $chunks; $i++) {
                $sleepSeconds = min(10, $remainingSeconds);

                Log::debug('Rate limit wait progress', [
                    'connection_id' => $rateLimit->connection_id,
                    'chunk' => $i + 1,
                    'total_chunks' => $chunks,
                    'sleeping_for' => $sleepSeconds,
                ]);

                sleep($sleepSeconds);
                $remainingSeconds -= $sleepSeconds;

                if ($remainingSeconds <= 0) {
                    break;
                }
            }
        } else {
            sleep($secondsToWait);
        }

        Log::info('Rate limit wait completed', [
            'connection_id' => $rateLimit->connection_id,
            'limit_type' => $limitType,
        ]);

        // Reset the appropriate limit counter after waiting
        $this->resetLimitCounter($rateLimit, $limitType);
    }

    /**
     * Reset limit counter after waiting
     */
    protected function resetLimitCounter(ExactRateLimit $rateLimit, string $limitType): void
    {
        if ($limitType === 'daily') {
            $rateLimit->update([
                'daily_remaining' => $rateLimit->daily_limit,
                'daily_reset_at' => now()->addDay()->timestamp,
            ]);
        } else {
            $rateLimit->update([
                'minutely_remaining' => $rateLimit->minutely_limit ?? 60,
                'minutely_reset_at' => now()->addMinute()->timestamp,
            ]);
        }
    }
}
