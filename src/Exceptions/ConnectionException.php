<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Exceptions;

/**
 * Exception for connection-related failures.
 *
 * Thrown when a connection cannot be established, is inactive,
 * or has invalid configuration.
 */
class ConnectionException extends ExactOnlineException
{
    public static function connectionNotFound(): self
    {
        return new self(
            'No active Exact Online connection found. '.
            'Please ensure a connection is configured and authenticated.'
        );
    }

    public static function connectionInactive(string $connectionId): self
    {
        return new self(
            "Exact Online connection '{$connectionId}' is marked as inactive. ".
            'Please reactivate the connection or complete the OAuth flow.'
        );
    }

    public static function tokensNotFound(string $connectionId): self
    {
        return new self(
            "No access or refresh tokens found for connection '{$connectionId}'. ".
            'Please complete the OAuth flow to authenticate with Exact Online.'
        );
    }

    public static function tokenRefreshFailed(string $connectionId, string $reason): self
    {
        return new self(
            "Failed to refresh access token for connection '{$connectionId}': {$reason}. ".
            'The user may need to re-authenticate with Exact Online.'
        );
    }

    public static function invalidConfiguration(string $reason): self
    {
        return new self(
            "Invalid Exact Online connection configuration: {$reason}"
        );
    }

    public static function authenticationRequired(string $connectionId): self
    {
        return new self(
            "Authentication required for connection '{$connectionId}'. ".
            'The user must complete the OAuth flow to establish a connection with Exact Online.'
        );
    }

    public static function divisionNotAccessible(string $division): self
    {
        return new self(
            "Division '{$division}' is not accessible with the current connection. ".
            'Please verify the division ID and ensure you have the necessary permissions.'
        );
    }

    public static function apiError(string $message, int $statusCode): self
    {
        return new self(
            "Exact Online API error (HTTP {$statusCode}): {$message}"
        );
    }
}
