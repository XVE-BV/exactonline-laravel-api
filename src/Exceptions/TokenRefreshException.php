<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Exceptions;

/**
 * Exception for token refresh failures.
 *
 * Thrown when access token refresh fails, including lock timeouts,
 * expired refresh tokens, and API errors during refresh.
 */
class TokenRefreshException extends ExactOnlineException
{
    public static function refreshFailed(string $connectionId, string $reason): self
    {
        $exception = new self(
            "Failed to refresh token for connection {$connectionId}: {$reason}"
        );

        return $exception->setConnectionId($connectionId);
    }

    public static function lockTimeout(string $connectionId): self
    {
        $exception = new self(
            "Timeout waiting for token refresh lock for connection {$connectionId}. ".
            'Another process may be taking too long to refresh the token.'
        );

        return $exception->setConnectionId($connectionId);
    }

    public static function maxRetriesExceeded(string $connectionId, int $attempts): self
    {
        $exception = new self(
            "Token refresh failed after {$attempts} attempts for connection {$connectionId}. ".
            'Please check your network connection and Exact Online status.'
        );

        return $exception
            ->setConnectionId($connectionId)
            ->addContext('attempts', $attempts);
    }

    public static function refreshTokenExpired(string $connectionId): self
    {
        $exception = new self(
            "Refresh token has expired for connection {$connectionId}. ".
            'User must re-authenticate with Exact Online.'
        );

        return $exception->setConnectionId($connectionId);
    }

    public static function invalidTokenResponse(string $connectionId): self
    {
        $exception = new self(
            "Received invalid token response from Exact Online for connection {$connectionId}. ".
            'The API response was missing required token fields.'
        );

        return $exception->setConnectionId($connectionId);
    }
}
