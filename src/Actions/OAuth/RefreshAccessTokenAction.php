<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\OAuth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Events\TokenRefreshed;
use Skylence\ExactonlineLaravelApi\Events\TokenRefreshFailed;
use Skylence\ExactonlineLaravelApi\Exceptions\TokenRefreshException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class RefreshAccessTokenAction
{
    /**
     * Refresh access token with distributed locking
     *
     * CRITICAL: 10-minute access token lifetime requires bulletproof refresh
     *
     * - Uses distributed lock to prevent concurrent refreshes
     * - Proactive refresh at 9 minutes (not on expiry)
     * - Automatic retry with exponential backoff (3 attempts)
     * - Handles race conditions in multi-server environments
     *
     * @return array{access_token: string, refresh_token: string, expires_at: int}
     *
     * @throws TokenRefreshException
     */
    public function execute(ExactConnection $connection): array
    {
        $lockKey = "exact-token-refresh:{$connection->id}";

        // Distributed lock with 30-second timeout
        $lock = Cache::lock($lockKey, 30);

        try {
            // Try to acquire lock
            if (! $lock->get()) {
                // Another process is refreshing, wait and return updated tokens
                Log::info('Token refresh lock not acquired, waiting for another process', [
                    'connection_id' => $connection->id,
                ]);

                return $this->waitForRefreshAndReturnTokens($connection);
            }

            // Double-check token hasn't been refreshed by another process
            $connection->refresh();
            if (! $this->tokenNeedsRefresh($connection)) {
                Log::info('Token already refreshed by another process', [
                    'connection_id' => $connection->id,
                    'expires_at' => $connection->token_expires_at,
                ]);

                return $this->extractTokens($connection);
            }

            // Perform actual token refresh with retry logic
            $tokens = $this->performTokenRefreshWithRetry($connection);

            // Store new tokens
            $this->storeTokens($connection, $tokens);

            // Dispatch event
            event(new TokenRefreshed($connection));

            Log::info('Token refresh successful', [
                'connection_id' => $connection->id,
                'new_expires_at' => $tokens['expires_at'],
            ]);

            return $tokens;

        } catch (\Throwable $e) {
            Log::error('Token refresh failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Dispatch failure event
            event(new TokenRefreshFailed($connection, $e));

            if ($e instanceof TokenRefreshException) {
                throw $e;
            }

            throw TokenRefreshException::refreshFailed(
                (string) $connection->id,
                $e->getMessage()
            );
        } finally {
            // Always release the lock
            $lock->release();
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
        return $connection->token_expires_at < (now()->getTimestamp() + 540);
    }

    /**
     * Perform token refresh with exponential backoff retry
     *
     * @return array{access_token: string, refresh_token: string, expires_at: int}
     *
     * @throws TokenRefreshException
     */
    protected function performTokenRefreshWithRetry(
        ExactConnection $connection,
        int $maxRetries = 3
    ): array {
        $attempt = 0;
        $baseDelay = 100; // milliseconds

        while ($attempt < $maxRetries) {
            try {
                return $this->performTokenRefresh($connection);
            } catch (\Throwable $e) {
                $attempt++;

                if ($attempt >= $maxRetries) {
                    throw TokenRefreshException::maxRetriesExceeded(
                        (string) $connection->id,
                        $attempt
                    );
                }

                // Exponential backoff: 100ms, 200ms, 400ms
                $delay = $baseDelay * (2 ** ($attempt - 1));
                usleep($delay * 1000);

                Log::warning('Token refresh retry', [
                    'connection_id' => $connection->id,
                    'attempt' => $attempt,
                    'delay_ms' => $delay,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw TokenRefreshException::maxRetriesExceeded(
            (string) $connection->id,
            $maxRetries
        );
    }

    /**
     * Actual token refresh using picqer's Connection
     *
     * @return array{access_token: string, refresh_token: string, expires_at: int}
     *
     * @throws TokenRefreshException
     */
    protected function performTokenRefresh(ExactConnection $connection): array
    {
        // Check if refresh token is expired (30 days)
        if ($connection->refresh_token_expires_at &&
            $connection->refresh_token_expires_at < now()->getTimestamp()) {
            throw TokenRefreshException::refreshTokenExpired((string) $connection->id);
        }

        $decryptedRefreshToken = $connection->getDecryptedRefreshToken();
        if (! $decryptedRefreshToken) {
            throw TokenRefreshException::refreshFailed(
                (string) $connection->id,
                'No refresh token available'
            );
        }

        try {
            $picqerConnection = $connection->getPicqerConnection();
            $picqerConnection->setRefreshToken($decryptedRefreshToken);

            // Trigger token refresh via picqer
            $picqerConnection->connect();

            // Get the new tokens
            $newAccessToken = $picqerConnection->getAccessToken();
            $newRefreshToken = $picqerConnection->getRefreshToken();
            $newExpiresAt = $picqerConnection->getTokenExpires();

            if (! $newAccessToken || ! $newRefreshToken || ! $newExpiresAt) {
                throw TokenRefreshException::invalidTokenResponse((string) $connection->id);
            }

            return [
                'access_token' => $newAccessToken,
                'refresh_token' => $newRefreshToken,
                'expires_at' => $newExpiresAt,
            ];

        } catch (\Picqer\Financials\Exact\ApiException $e) {
            // Check if it's a refresh token expired error
            if (str_contains($e->getMessage(), 'refresh_token') ||
                str_contains($e->getMessage(), 'invalid_grant')) {
                throw TokenRefreshException::refreshTokenExpired((string) $connection->id);
            }

            throw TokenRefreshException::refreshFailed(
                (string) $connection->id,
                'Exact Online API error: '.$e->getMessage()
            );
        } catch (\Exception $e) {
            throw TokenRefreshException::refreshFailed(
                (string) $connection->id,
                'Unexpected error: '.$e->getMessage()
            );
        }
    }

    /**
     * Store refreshed tokens securely
     *
     * @param  array{access_token: string, refresh_token: string, expires_at: int}  $tokens
     */
    protected function storeTokens(ExactConnection $connection, array $tokens): void
    {
        // The model will automatically encrypt tokens via mutators
        $connection->update([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_expires_at' => $tokens['expires_at'],
            'last_token_refresh_at' => now(),
            // Refresh token expires in 30 days from now
            'refresh_token_expires_at' => now()->addDays(30)->timestamp,
        ]);
    }

    /**
     * Wait for another process to finish refresh, then return updated tokens
     *
     * @return array{access_token: string, refresh_token: string, expires_at: int}
     *
     * @throws TokenRefreshException
     */
    protected function waitForRefreshAndReturnTokens(
        ExactConnection $connection,
        int $maxWaitMs = 3000
    ): array {
        $waited = 0;
        $checkInterval = 100; // ms

        while ($waited < $maxWaitMs) {
            usleep($checkInterval * 1000);
            $waited += $checkInterval;

            $connection->refresh();

            if (! $this->tokenNeedsRefresh($connection)) {
                Log::info('Token refresh completed by another process', [
                    'connection_id' => $connection->id,
                    'waited_ms' => $waited,
                ]);

                return $this->extractTokens($connection);
            }
        }

        throw TokenRefreshException::lockTimeout((string) $connection->id);
    }

    /**
     * Extract tokens from connection
     *
     * @return array{access_token: string, refresh_token: string, expires_at: int}
     *
     * @throws TokenRefreshException
     */
    protected function extractTokens(ExactConnection $connection): array
    {
        $accessToken = $connection->getDecryptedAccessToken();
        $refreshToken = $connection->getDecryptedRefreshToken();

        if (! $accessToken || ! $refreshToken) {
            throw TokenRefreshException::refreshFailed(
                (string) $connection->id,
                'Tokens are missing from the connection'
            );
        }

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => $connection->token_expires_at ?? 0,
        ];
    }
}
