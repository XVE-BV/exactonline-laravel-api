<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Validation;

use Skylence\ExactonlineLaravelApi\Exceptions\ExactOnlineException;

/**
 * Exception for pre-flight payload validation failures.
 *
 * Thrown when the payload data fails validation before being sent
 * to the Exact Online API.
 */
class PayloadValidationException extends ExactOnlineException
{
    protected string $operation;

    protected array $validationErrors;

    public function __construct(
        string $entity,
        string $operation,
        array $errors
    ) {
        $this->operation = $operation;
        $this->validationErrors = $errors;

        $message = "Payload validation failed for {$entity} ({$operation}): ".
                   json_encode($errors, JSON_PRETTY_PRINT);

        parent::__construct($message);

        $this->setEntity($entity);
        $this->setContext([
            'operation' => $operation,
            'errors' => $errors,
        ]);
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }
}
