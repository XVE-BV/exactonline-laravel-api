<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Validation;

use Exception;

class PayloadValidationException extends Exception
{
    public function __construct(
        public readonly string $entity,
        public readonly string $operation,
        public readonly array $errors,
    ) {
        $message = "Payload validation failed for {$entity} ({$operation}): ".
                   json_encode($errors, JSON_PRETTY_PRINT);
        parent::__construct($message);
    }

    public function getValidationErrors(): array
    {
        return $this->errors;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }
}
