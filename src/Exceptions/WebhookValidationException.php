<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Exceptions;

/**
 * Exception for webhook validation failures.
 *
 * Thrown when webhook signature validation fails, the payload is invalid,
 * or the webhook topic is unknown.
 */
class WebhookValidationException extends ExactOnlineException
{
    public static function invalidSignature(string $expectedSignature, string $actualSignature): self
    {
        // Don't expose the full signatures in error messages for security
        $expectedPrefix = substr($expectedSignature, 0, 10);
        $actualPrefix = substr($actualSignature, 0, 10);

        return new self(
            'Webhook signature validation failed. '.
            "Expected signature starting with '{$expectedPrefix}...', ".
            "but received '{$actualPrefix}...'. The webhook payload may have been tampered with."
        );
    }

    public static function missingSignature(): self
    {
        return new self(
            'Webhook request is missing the required signature header. '.
            'Ensure the webhook is properly configured in Exact Online.'
        );
    }

    public static function invalidPayload(string $reason): self
    {
        return new self(
            "Invalid webhook payload: {$reason}"
        );
    }

    public static function unknownTopic(string $topic): self
    {
        return new self(
            "Unknown webhook topic '{$topic}'. ".
            'Please check your webhook configuration and ensure this topic is handled.'
        );
    }

    public static function processingFailed(string $topic, string $reason): self
    {
        return new self(
            "Failed to process webhook for topic '{$topic}': {$reason}"
        );
    }
}
