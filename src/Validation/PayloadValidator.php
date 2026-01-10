<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Validation;

class PayloadValidator
{
    public function __construct(
        protected SchemaLoader $schemaLoader,
        protected FieldValidator $fieldValidator,
        protected bool $enabled = true,
        protected bool $strict = false,
    ) {}

    /**
     * Validate a payload for a create operation.
     *
     * @throws PayloadValidationException
     */
    public function validateCreate(string $entity, array $data): void
    {
        $this->validate($entity, $data, 'create');
    }

    /**
     * Validate a payload for an update operation.
     *
     * @throws PayloadValidationException
     */
    public function validateUpdate(string $entity, array $data): void
    {
        $this->validate($entity, $data, 'update');
    }

    /**
     * Check if a schema exists for the given entity.
     */
    public function hasSchema(string $entity): bool
    {
        return $this->schemaLoader->hasSchema($entity);
    }

    /**
     * Validate a payload against the entity schema.
     *
     * @throws PayloadValidationException
     */
    protected function validate(string $entity, array $data, string $operation): void
    {
        // Skip validation if disabled
        if (! $this->enabled) {
            return;
        }

        // Skip if no schema exists
        if (! $this->schemaLoader->hasSchema($entity)) {
            return;
        }

        $fields = $this->schemaLoader->getFields($entity);
        $errors = [];

        // Check required fields (create only)
        if ($operation === 'create') {
            foreach ($fields as $name => $rules) {
                $isRequired = $rules['required'] ?? false;
                if ($isRequired && ! array_key_exists($name, $data)) {
                    $errors[$name][] = "Field '{$name}' is required";
                }
            }
        }

        // Validate provided fields
        foreach ($data as $name => $value) {
            // Check for unknown fields in strict mode
            if (! isset($fields[$name])) {
                if ($this->strict) {
                    $errors[$name][] = "Unknown field '{$name}'";
                }

                continue;
            }

            $rules = $fields[$name];

            // Check read-only fields
            if ($rules['readOnly'] ?? false) {
                $errors[$name][] = "Field '{$name}' is read-only and cannot be set";

                continue;
            }

            // Skip null values if nullable (default is nullable)
            if ($value === null) {
                $isNullable = $rules['nullable'] ?? true;
                if (! $isNullable) {
                    $errors[$name][] = "Field '{$name}' cannot be null";
                }

                continue;
            }

            // Validate field value
            $fieldErrors = $this->fieldValidator->validate($name, $value, $rules);
            if (! empty($fieldErrors)) {
                $errors[$name] = array_merge($errors[$name] ?? [], $fieldErrors);
            }
        }

        // Throw if any validation errors occurred
        if (! empty($errors)) {
            throw new PayloadValidationException($entity, $operation, $errors);
        }
    }

    /**
     * Enable or disable validation.
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Enable or disable strict mode.
     */
    public function setStrict(bool $strict): self
    {
        $this->strict = $strict;

        return $this;
    }
}
