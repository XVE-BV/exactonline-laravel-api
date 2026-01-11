<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Exceptions;

/**
 * Exception for Exact Online API errors.
 *
 * Thrown when the API returns an error response, including validation errors,
 * server errors, and other HTTP error responses.
 */
class ApiException extends ExactOnlineException
{
    protected int $statusCode = 0;

    protected ?string $endpoint = null;

    protected ?string $method = null;

    protected ?array $responseBody = null;

    protected array $validationErrors = [];

    public function __construct(
        string $message = '',
        int $statusCode = 0,
        ?\Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Create exception from HTTP response.
     */
    public static function fromResponse(
        int $statusCode,
        string $message,
        ?string $endpoint = null,
        ?string $method = null
    ): self {
        $exception = new self(
            "Exact Online API error (HTTP {$statusCode}): {$message}",
            $statusCode
        );

        $exception->endpoint = $endpoint;
        $exception->method = $method;

        return $exception;
    }

    /**
     * Create exception for validation error from API.
     */
    public static function validationFailed(
        string $entity,
        array $errors,
        ?string $connectionId = null
    ): self {
        $errorMessages = [];
        foreach ($errors as $field => $fieldErrors) {
            if (is_array($fieldErrors)) {
                $errorMessages[] = "{$field}: ".implode(', ', $fieldErrors);
            } else {
                $errorMessages[] = "{$field}: {$fieldErrors}";
            }
        }

        $message = "Exact Online validation failed for {$entity}: ".implode('; ', $errorMessages);

        $exception = new self($message, 400);
        $exception->setEntity($entity);
        $exception->validationErrors = $errors;

        if ($connectionId) {
            $exception->setConnectionId($connectionId);
        }

        return $exception;
    }

    /**
     * Create exception for bad request (400).
     */
    public static function badRequest(string $message, ?string $endpoint = null): self
    {
        return self::fromResponse(400, $message, $endpoint);
    }

    /**
     * Create exception for unauthorized (401).
     */
    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return self::fromResponse(
            401,
            "{$message}. The access token may be invalid or expired."
        );
    }

    /**
     * Create exception for forbidden (403).
     */
    public static function forbidden(string $entity, ?string $operation = null): self
    {
        $message = "Access denied to {$entity}";
        if ($operation) {
            $message .= " for operation '{$operation}'";
        }
        $message .= '. Check your Exact Online permissions.';

        $exception = self::fromResponse(403, $message);
        $exception->setEntity($entity);

        return $exception;
    }

    /**
     * Create exception for not found (404).
     */
    public static function notFound(string $entity, string $id): self
    {
        $exception = self::fromResponse(
            404,
            "{$entity} with ID '{$id}' not found in Exact Online"
        );
        $exception->setEntity($entity);
        $exception->addContext('entity_id', $id);

        return $exception;
    }

    /**
     * Create exception for conflict (409).
     */
    public static function conflict(string $entity, string $message): self
    {
        $exception = self::fromResponse(
            409,
            "Conflict updating {$entity}: {$message}"
        );
        $exception->setEntity($entity);

        return $exception;
    }

    /**
     * Create exception for server error (500).
     */
    public static function serverError(string $message = 'Internal server error'): self
    {
        return self::fromResponse(
            500,
            "Exact Online server error: {$message}. Please try again later."
        );
    }

    /**
     * Create exception for service unavailable (503).
     */
    public static function serviceUnavailable(): self
    {
        return self::fromResponse(
            503,
            'Exact Online is temporarily unavailable. Please try again later.'
        );
    }

    /**
     * Create exception for timeout.
     */
    public static function timeout(string $endpoint): self
    {
        return self::fromResponse(
            408,
            "Request to '{$endpoint}' timed out. Please try again.",
            $endpoint
        );
    }

    /**
     * Create exception from Picqer API exception.
     */
    public static function fromPicqerException(
        \Throwable $exception,
        ?string $entity = null,
        ?string $connectionId = null
    ): self {
        $message = $exception->getMessage();
        $statusCode = $exception->getCode() ?: 500;

        // Parse common error patterns
        if (str_contains($message, 'not found') || str_contains($message, '404')) {
            $statusCode = 404;
        } elseif (str_contains($message, 'unauthorized') || str_contains($message, '401')) {
            $statusCode = 401;
        } elseif (str_contains($message, 'forbidden') || str_contains($message, '403')) {
            $statusCode = 403;
        } elseif (str_contains($message, 'rate limit') || str_contains($message, '429')) {
            $statusCode = 429;
        }

        $apiException = new self($message, $statusCode, $exception);

        if ($entity) {
            $apiException->setEntity($entity);
        }

        if ($connectionId) {
            $apiException->setConnectionId($connectionId);
        }

        return $apiException;
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the API endpoint.
     */
    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    /**
     * Set the API endpoint.
     */
    public function setEndpoint(string $endpoint): self
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Get the HTTP method.
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Set the HTTP method.
     */
    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get the response body.
     */
    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }

    /**
     * Set the response body.
     */
    public function setResponseBody(array $responseBody): self
    {
        $this->responseBody = $responseBody;

        return $this;
    }

    /**
     * Get validation errors from the API.
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Check if this is a client error (4xx).
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if this is a server error (5xx).
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500;
    }

    /**
     * Check if this is a validation error.
     */
    public function isValidationError(): bool
    {
        return ! empty($this->validationErrors);
    }

    /**
     * Check if this error is retryable.
     */
    public function isRetryable(): bool
    {
        // Server errors and rate limits are typically retryable
        return $this->statusCode >= 500 ||
               $this->statusCode === 429 ||
               $this->statusCode === 408;
    }

    /**
     * Get exception data for logging.
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), array_filter([
            'status_code' => $this->statusCode,
            'endpoint' => $this->endpoint,
            'method' => $this->method,
            'validation_errors' => $this->validationErrors ?: null,
        ]));
    }
}
