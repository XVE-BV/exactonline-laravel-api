<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Concerns;

use XVE\ExactonlineLaravelApi\Validation\PayloadValidationException;
use XVE\ExactonlineLaravelApi\Validation\PayloadValidator;

trait ValidatesPayload
{
    /**
     * Validate a payload for a create operation.
     *
     * @throws PayloadValidationException
     */
    /** @param array<string, mixed> $data */
    protected function validateCreatePayload(string $entity, array $data): void
    {
        $this->getPayloadValidator()->validateCreate($entity, $data);
    }

    /**
     * Validate a payload for an update operation.
     *
     * @param  array<string, mixed>  $data
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
