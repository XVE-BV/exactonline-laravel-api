<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Exceptions;

use Exception;

class TokenRefreshException extends Exception
{
    public static function refreshFailed(string $connectionId, string $reason): self
    {
        return new self(
            "Failed to refresh token for connection {$connectionId}: {$reason}"
        );
    }

    public static function lockTimeout(string $connectionId): self
    {
        return new self(
            "Timeout waiting for token refresh lock for connection {$connectionId}. ".
            'Another process may be taking too long to refresh the token.'
        );
    }

    public static function maxRetriesExceeded(string $connectionId, int $attempts): self
    {
        return new self(
            "Token refresh failed after {$attempts} attempts for connection {$connectionId}. ".
            'Please check your network connection and Exact Online status.'
        );
    }

    public static function refreshTokenExpired(string $connectionId): self
    {
        return new self(
            "Refresh token has expired for connection {$connectionId}. ".
            'User must re-authenticate with Exact Online.'
        );
    }

    public static function invalidTokenResponse(string $connectionId): self
    {
        return new self(
            "Received invalid token response from Exact Online for connection {$connectionId}. ".
            'The API response was missing required token fields.'
        );
    }
}
