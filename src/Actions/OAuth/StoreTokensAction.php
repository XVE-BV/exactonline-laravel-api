<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\OAuth;

use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class StoreTokensAction
{
    /**
     * Store OAuth tokens securely in the database
     *
     * This action handles the secure storage of access and refresh tokens,
     * including automatic encryption and setting appropriate expiration timestamps.
     *
     * @param ExactConnection $connection
     * @param array{access_token: string, refresh_token: string, expires_at?: int} $tokens
     * @return ExactConnection
     */
    public function execute(
        ExactConnection $connection,
        array $tokens
    ): ExactConnection {
        $this->validateTokens($tokens);

        // Calculate expiration timestamps
        $tokenExpiresAt = $this->calculateTokenExpiration($tokens);
        $refreshTokenExpiresAt = $this->calculateRefreshTokenExpiration();

        // Update connection with encrypted tokens (encryption happens in model mutators)
        $connection->update([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_expires_at' => $tokenExpiresAt,
            'refresh_token_expires_at' => $refreshTokenExpiresAt,
            'last_token_refresh_at' => now(),
            'is_active' => true,
        ]);

        Log::info('Tokens stored successfully', [
            'connection_id' => $connection->id,
            'token_expires_at' => $tokenExpiresAt,
            'refresh_token_expires_at' => $refreshTokenExpiresAt,
        ]);

        return $connection->fresh();
    }

    /**
     * Validate the token array structure
     *
     * @param array<string, mixed> $tokens
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateTokens(array $tokens): void
    {
        if (empty($tokens['access_token'])) {
            throw new \InvalidArgumentException('Access token is required');
        }

        if (empty($tokens['refresh_token'])) {
            throw new \InvalidArgumentException('Refresh token is required');
        }

        if (! is_string($tokens['access_token'])) {
            throw new \InvalidArgumentException('Access token must be a string');
        }

        if (! is_string($tokens['refresh_token'])) {
            throw new \InvalidArgumentException('Refresh token must be a string');
        }

        if (isset($tokens['expires_at']) && ! is_int($tokens['expires_at'])) {
            throw new \InvalidArgumentException('Expires at must be an integer timestamp');
        }
    }

    /**
     * Calculate the access token expiration timestamp
     *
     * @param array{expires_at?: int} $tokens
     * @return int
     */
    protected function calculateTokenExpiration(array $tokens): int
    {
        // If expires_at is provided, use it
        if (isset($tokens['expires_at']) && $tokens['expires_at'] > 0) {
            return $tokens['expires_at'];
        }

        // Default to 10 minutes from now (Exact Online standard)
        return now()->addMinutes(10)->timestamp;
    }

    /**
     * Calculate the refresh token expiration timestamp
     *
     * Exact Online refresh tokens are valid for 30 days from acquisition.
     *
     * @return int
     */
    protected function calculateRefreshTokenExpiration(): int
    {
        return now()->addDays(30)->timestamp;
    }
}
