<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Support\Results;

/**
 * Value object representing the result of a sync operation.
 */
final readonly class SyncResult
{
    public function __construct(
        public string $id,
        public ?string $code,
        public bool $created,
        public bool $updated,
        public ?string $error = null,
    ) {}

    /**
     * Create a successful create result.
     */
    public static function created(string $id, ?string $code = null): self
    {
        return new self(
            id: $id,
            code: $code,
            created: true,
            updated: false,
        );
    }

    /**
     * Create a successful update result.
     */
    public static function updated(string $id, ?string $code = null): self
    {
        return new self(
            id: $id,
            code: $code,
            created: false,
            updated: true,
        );
    }

    /**
     * Create a failed result.
     */
    public static function failed(string $error, ?string $id = null): self
    {
        return new self(
            id: $id ?? '',
            code: null,
            created: false,
            updated: false,
            error: $error,
        );
    }

    /**
     * Check if the sync was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->error === null && ($this->created || $this->updated);
    }

    /**
     * Check if the sync failed.
     */
    public function isFailed(): bool
    {
        return $this->error !== null;
    }
}
