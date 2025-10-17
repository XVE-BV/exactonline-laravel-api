<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Exceptions;

use Exception;

class ConnectionException extends Exception
{
    public static function notFound(string $connectionId): self
    {
        return new self(
            "Exact Online connection with ID '{$connectionId}' not found. " .
            "Please ensure the connection exists and is properly configured."
        );
    }

    public static function inactive(string $connectionId): self
    {
        return new self(
            "Exact Online connection '{$connectionId}' is marked as inactive. " .
            "Please reactivate the connection or use a different one."
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
            "Authentication required for connection '{$connectionId}'. " .
            "The user must complete the OAuth flow to establish a connection with Exact Online."
        );
    }

    public static function divisionNotAccessible(string $division): self
    {
        return new self(
            "Division '{$division}' is not accessible with the current connection. " .
            "Please verify the division ID and ensure you have the necessary permissions."
        );
    }

    public static function apiError(string $message, int $statusCode): self
    {
        return new self(
            "Exact Online API error (HTTP {$statusCode}): {$message}"
        );
    }
}
