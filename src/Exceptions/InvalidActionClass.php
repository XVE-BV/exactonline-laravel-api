<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Exceptions;

use Exception;

class InvalidActionClass extends Exception
{
    public static function notConfigured(string $actionName): self
    {
        return new self(
            "No action class configured for '{$actionName}'. " .
            "Please check your exactonline-laravel-api.actions config."
        );
    }

    public static function doesNotExist(string $actionName, string $actionClass): self
    {
        return new self(
            "The action class '{$actionClass}' configured for '{$actionName}' does not exist. " .
            "Please check your exactonline-laravel-api.actions config."
        );
    }

    public static function invalidType(string $actionName, string $expectedType, string $actualClass): self
    {
        return new self(
            "The action class '{$actualClass}' configured for '{$actionName}' is not of the expected type '{$expectedType}'. " .
            "Please ensure your custom action class is compatible with the expected type."
        );
    }
}
