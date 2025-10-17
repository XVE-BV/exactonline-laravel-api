<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\WaitForRateLimitResetAction;
use Skylence\ExactonlineLaravelApi\Exceptions\RateLimitExceededException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;
use Symfony\Component\HttpFoundation\Response;

class CheckExactRateLimit
{
    /**
     * Handle an incoming request.
     *
     * This middleware checks Exact Online rate limits and handles them according to configuration:
     * - For minutely limits: can wait or throw exception based on config
     * - For daily limits: usually throws exception (waiting 24 hours is not practical)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the connection from the request (set by EnsureValidExactConnection middleware)
        $connection = $this->getConnection($request);

        if ($connection === null) {
            // No connection, skip rate limit check
            // This allows the middleware to be used without EnsureValidExactConnection
            return $next($request);
        }

        try {
            // Check current rate limit status
            $checkRateLimitAction = Config::getAction(
                'check_rate_limit',
                CheckRateLimitAction::class
            );

            $checkRateLimitAction->execute($connection);

        } catch (RateLimitExceededException $e) {
            // Handle rate limit exceeded based on type and configuration
            return $this->handleRateLimitExceeded($e, $connection, $request, $next);
        }

        return $next($request);
    }

    /**
     * Get the Exact Online connection from the request
     */
    protected function getConnection(Request $request): ?ExactConnection
    {
        // Try to get from request attributes (set by EnsureValidExactConnection)
        $connection = $request->attributes->get('exactConnection');

        if ($connection instanceof ExactConnection) {
            return $connection;
        }

        // Try to get from user resolver (also set by EnsureValidExactConnection)
        $userResolver = $request->getUserResolver();
        if ($userResolver !== null) {
            $resolved = $userResolver();
            if ($resolved instanceof ExactConnection) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * Handle rate limit exceeded
     *
     *
     * @throws RateLimitExceededException
     */
    protected function handleRateLimitExceeded(
        RateLimitExceededException $exception,
        ExactConnection $connection,
        Request $request,
        Closure $next
    ): Response {
        $isMinutelyLimit = $exception->isMinutelyLimit();
        $isDailyLimit = $exception->isDailyLimit();

        // Handle minutely limit
        if ($isMinutelyLimit) {
            $waitOnMinutelyLimit = config('exactonline-laravel-api.rate_limiting.wait_on_minutely_limit', true);

            if ($waitOnMinutelyLimit) {
                Log::warning('Exact Online minutely rate limit hit, waiting for reset', [
                    'connection_id' => $connection->id,
                    'reset_at' => $exception->getResetAt(),
                ]);

                // Wait for rate limit reset
                $waitAction = Config::getAction(
                    'wait_for_rate_limit_reset',
                    WaitForRateLimitResetAction::class
                );

                $waitAction->execute($connection, $exception->getResetAt());

                // Retry the request after waiting
                return $next($request);
            }
        }

        // Handle daily limit
        if ($isDailyLimit) {
            $throwOnDailyLimit = config('exactonline-laravel-api.rate_limiting.throw_on_daily_limit', true);

            if (! $throwOnDailyLimit) {
                // Not recommended: waiting 24 hours is not practical
                Log::error('Exact Online daily rate limit hit, attempting to wait (not recommended)', [
                    'connection_id' => $connection->id,
                    'reset_at' => $exception->getResetAt(),
                ]);

                $waitAction = Config::getAction(
                    'wait_for_rate_limit_reset',
                    WaitForRateLimitResetAction::class
                );

                // This will likely timeout or be impractical
                $waitAction->execute($connection, $exception->getResetAt());

                return $next($request);
            }
        }

        // If we reach here, throw the exception
        // This happens when:
        // - Minutely limit hit and wait_on_minutely_limit is false
        // - Daily limit hit and throw_on_daily_limit is true (default)
        Log::error('Exact Online rate limit exceeded, throwing exception', [
            'connection_id' => $connection->id,
            'type' => $isMinutelyLimit ? 'minutely' : 'daily',
            'reset_at' => $exception->getResetAt(),
        ]);

        throw $exception;
    }

    /**
     * Create a rate limit response
     */
    protected function createRateLimitResponse(RateLimitExceededException $exception): Response
    {
        $retryAfter = max(1, $exception->getResetAt() - now()->timestamp);

        return response()->json([
            'error' => 'Rate limit exceeded',
            'message' => $exception->getMessage(),
            'type' => $exception->isMinutelyLimit() ? 'minutely' : 'daily',
            'retry_after' => $retryAfter,
            'reset_at' => $exception->getResetAt(),
        ], 429)
            ->header('Retry-After', (string) $retryAfter)
            ->header('X-RateLimit-Type', $exception->isMinutelyLimit() ? 'minutely' : 'daily')
            ->header('X-RateLimit-Reset', (string) $exception->getResetAt());
    }
}
