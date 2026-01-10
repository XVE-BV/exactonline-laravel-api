<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Validation;

use DateTime;
use DateTimeInterface;

class FieldValidator
{
    /**
     * Validate a field value against its rules.
     *
     * @param  string  $field  The field name
     * @param  mixed  $value  The value to validate
     * @param  array  $rules  The validation rules
     * @return array<string> Array of error messages (empty if valid)
     */
    public function validate(string $field, mixed $value, array $rules): array
    {
        $errors = [];
        $type = $rules['type'] ?? 'string';

        // Type validation
        if (! $this->validateType($value, $type)) {
            $errors[] = "Field '{$field}' must be of type {$type}, got ".gettype($value);

            // Skip further validation if type is wrong
            return $errors;
        }

        // String validations
        if ($type === 'string' && is_string($value)) {
            if (isset($rules['maxLength']) && mb_strlen($value) > $rules['maxLength']) {
                $errors[] = "Field '{$field}' exceeds maximum length of {$rules['maxLength']} characters";
            }
            if (isset($rules['minLength']) && mb_strlen($value) < $rules['minLength']) {
                $errors[] = "Field '{$field}' must be at least {$rules['minLength']} characters";
            }
        }

        // Format validation
        if (isset($rules['format']) && is_string($value)) {
            if (! $this->validateFormat($value, $rules['format'])) {
                $errors[] = "Field '{$field}' has invalid {$rules['format']} format";
            }
        }

        // Enum validation
        if (isset($rules['enum']) && ! in_array($value, $rules['enum'], true)) {
            $allowed = implode(', ', array_map(fn ($v) => "'{$v}'", $rules['enum']));
            $errors[] = "Field '{$field}' must be one of: {$allowed}";
        }

        // Numeric range validation
        if (in_array($type, ['int16', 'int32', 'int64', 'double', 'decimal', 'byte']) && is_numeric($value)) {
            if (isset($rules['min']) && $value < $rules['min']) {
                $errors[] = "Field '{$field}' must be at least {$rules['min']}";
            }
            if (isset($rules['max']) && $value > $rules['max']) {
                $errors[] = "Field '{$field}' must be at most {$rules['max']}";
            }
        }

        // Byte-specific validation
        if ($type === 'byte' && is_int($value)) {
            if ($value < 0 || $value > 255) {
                $errors[] = "Field '{$field}' must be between 0 and 255";
            }
        }

        return $errors;
    }

    protected function validateType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'guid' => is_string($value) && $this->isValidGuid($value),
            'int16', 'int32', 'int64' => is_int($value) || (is_string($value) && ctype_digit(ltrim($value, '-'))),
            'double', 'decimal' => is_numeric($value),
            'boolean' => is_bool($value),
            'byte' => is_int($value) && $value >= 0 && $value <= 255,
            'datetime' => $this->isValidDateTime($value),
            'binary' => is_string($value),
            'collection' => is_array($value),
            default => true, // Allow unknown types to pass
        };
    }

    protected function validateFormat(string $value, string $format): bool
    {
        return match ($format) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'phone' => preg_match('/^[\d\s\-\+\(\)]+$/', $value) === 1,
            default => true,
        };
    }

    protected function isValidGuid(string $value): bool
    {
        // Standard UUID format: 8-4-4-4-12 hex digits
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }

    protected function isValidDateTime(mixed $value): bool
    {
        if ($value instanceof DateTimeInterface) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        // Try parsing common datetime formats
        $formats = [
            'Y-m-d\TH:i:s',
            'Y-m-d\TH:i:sP',
            'Y-m-d\TH:i:s.uP',
            'Y-m-d H:i:s',
            'Y-m-d',
        ];

        foreach ($formats as $format) {
            $parsed = DateTime::createFromFormat($format, $value);
            if ($parsed !== false) {
                return true;
            }
        }

        return strtotime($value) !== false;
    }
}
