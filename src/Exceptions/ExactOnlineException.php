<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for all Exact Online package exceptions.
 *
 * This provides a common parent for catching all package-related exceptions
 * and includes context about the connection and entity involved.
 */
class ExactOnlineException extends Exception
{
    protected ?string $connectionId = null;

    protected ?string $entity = null;

    protected array $context = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Set the connection ID associated with this exception.
     */
    public function setConnectionId(?string $connectionId): self
    {
        $this->connectionId = $connectionId;

        return $this;
    }

    /**
     * Get the connection ID associated with this exception.
     */
    public function getConnectionId(): ?string
    {
        return $this->connectionId;
    }

    /**
     * Set the entity type associated with this exception.
     */
    public function setEntity(?string $entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get the entity type associated with this exception.
     */
    public function getEntity(): ?string
    {
        return $this->entity;
    }

    /**
     * Set additional context for this exception.
     */
    public function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Add context to this exception.
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;

        return $this;
    }

    /**
     * Get the additional context for this exception.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get all exception data as an array for logging.
     */
    public function toArray(): array
    {
        return array_filter([
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'connection_id' => $this->connectionId,
            'entity' => $this->entity,
            'context' => $this->context ?: null,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ]);
    }

    /**
     * Create an exception with connection context.
     */
    public static function withConnection(string $connectionId, string $message): static
    {
        $exception = new static($message);
        $exception->setConnectionId($connectionId);

        return $exception;
    }
}
