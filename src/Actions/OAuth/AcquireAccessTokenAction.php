<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\OAuth;

use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Events\TokenAcquired;
use Skylence\ExactonlineLaravelApi\Exceptions\TokenRefreshException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class AcquireAccessTokenAction
{
    /**
     * Acquire access token using authorization code
     *
     * This action exchanges an OAuth authorization code for access and refresh tokens.
     * Used during the initial OAuth flow after user authorizes the application.
     *
     * @return array{access_token: string, refresh_token: string, expires_at: int}
     *
     * @throws TokenRefreshException
     * @throws \InvalidArgumentException
     */
    public function execute(
        ExactConnection $connection,
        string $authorizationCode
    ): array {
        $this->validateAuthorizationCode($authorizationCode);

        try {
            // Exchange code for tokens
            $tokens = $this->exchangeCodeForTokens($connection, $authorizationCode);

            // Store tokens in the database
            $this->storeTokens($connection, $tokens);

            // Dispatch event
            event(new TokenAcquired($connection));

            Log::info('Access token acquired successfully', [
                'connection_id' => $connection->id,
                'expires_at' => $tokens['expires_at'],
            ]);

            return $tokens;

        } catch (\Throwable $e) {
            Log::error('Failed to acquire access token', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof TokenRefreshException) {
                throw $e;
            }

            throw TokenRefreshException::refreshFailed(
                (string) $connection->id,
                'Failed to exchange authorization code: '.$e->getMessage()
            );
        }
    }

    /**
     * Validate the authorization code
     *
     * @throws \InvalidArgumentException
     */
    protected function validateAuthorizationCode(string $code): void
    {
        if (empty($code)) {
            throw new \InvalidArgumentException('Authorization code cannot be empty');
        }

        // Authorization codes are typically 30-50 characters
        if (strlen($code) < 20 || strlen($code) > 200) {
            throw new \InvalidArgumentException('Invalid authorization code format');
        }
    }

    /**
     * Exchange authorization code for tokens using picqer Connection
     *
     * @return array{access_token: string, refresh_token: string, expires_at: int}
     *
     * @throws TokenRefreshException
     */
    protected function exchangeCodeForTokens(
        ExactConnection $connection,
        string $code
    ): array {
        try {
            $picqerConnection = $connection->getPicqerConnection();

            // Set the authorization code
            $picqerConnection->setAuthorizationCode($code);

            // Exchange code for tokens
            $picqerConnection->connect();

            // Get the tokens
            $accessToken = $picqerConnection->getAccessToken();
            $refreshToken = $picqerConnection->getRefreshToken();
            $expiresAt = $picqerConnection->getTokenExpires();

            if (! $accessToken || ! $refreshToken) {
                throw TokenRefreshException::invalidTokenResponse((string) $connection->id);
            }

            // Also get the division if we don't have one yet
            if (! $connection->division) {
                try {
                    $division = $picqerConnection->getDivision();
                    if ($division) {
                        $connection->division = $division;
                    }
                } catch (\Exception $e) {
                    // Division retrieval is not critical, log and continue
                    Log::warning('Could not retrieve division', [
                        'connection_id' => $connection->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => $expiresAt ?: now()->addMinutes(10)->timestamp,
            ];

        } catch (\Picqer\Financials\Exact\ApiException $e) {
            // Check for common OAuth errors
            if (str_contains($e->getMessage(), 'invalid_grant')) {
                throw TokenRefreshException::refreshFailed(
                    (string) $connection->id,
                    'Invalid or expired authorization code'
                );
            }

            if (str_contains($e->getMessage(), 'invalid_client')) {
                throw TokenRefreshException::refreshFailed(
                    (string) $connection->id,
                    'Invalid client credentials. Please check your OAuth configuration.'
                );
            }

            throw TokenRefreshException::refreshFailed(
                (string) $connection->id,
                'Exact Online API error: '.$e->getMessage()
            );
        } catch (\Exception $e) {
            throw TokenRefreshException::refreshFailed(
                (string) $connection->id,
                'Failed to exchange authorization code: '.$e->getMessage()
            );
        }
    }

    /**
     * Store the acquired tokens securely
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
            // Refresh token is valid for 30 days from acquisition
            'refresh_token_expires_at' => now()->addDays(30)->timestamp,
            'is_active' => true,
        ]);
    }
}
