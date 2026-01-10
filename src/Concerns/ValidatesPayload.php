<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Concerns;

use Skylence\ExactonlineLaravelApi\Validation\PayloadValidationException;
use Skylence\ExactonlineLaravelApi\Validation\PayloadValidator;

trait ValidatesPayload
{
    /**
     * Validate a payload for a create operation.
     *
     * @throws PayloadValidationException
     */
    protected function validateCreatePayload(string $entity, array $data): void
    {
        $this->getPayloadValidator()->validateCreate($entity, $data);
    }

    /**
     * Validate a payload for an update operation.
     *
     * @throws PayloadValidationException
     */
    protected function validateUpdatePayload(string $entity, array $data): void
    {
        $this->getPayloadValidator()->validateUpdate($entity, $data);
    }

    /**
     * Check if validation is available for the entity.
     */
    protected function canValidatePayload(string $entity): bool
    {
        return $this->getPayloadValidator()->hasSchema($entity);
    }

    /**
     * Get the payload validator instance.
     */
    protected function getPayloadValidator(): PayloadValidator
    {
        return app(PayloadValidator::class);
    }
}
