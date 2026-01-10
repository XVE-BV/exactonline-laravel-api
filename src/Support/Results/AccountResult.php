<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Support\Results;

/**
 * Value object representing an Exact Online Account (Customer/Supplier).
 */
final readonly class AccountResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public ?string $code,
        public string $name,
        public ?string $vatNumber,
        public ?string $email,
        public ?string $phone,
        public ?string $status,
        public bool $isSupplier,
        public bool $isCustomer,
        public array $raw,
    ) {}

    /**
     * Create from Exact API response.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        return new self(
            id: $data['ID'] ?? '',
            code: $data['Code'] ?? null,
            name: $data['Name'] ?? '',
            vatNumber: $data['VATNumber'] ?? null,
            email: $data['Email'] ?? null,
            phone: $data['Phone'] ?? null,
            status: $data['Status'] ?? null,
            isSupplier: (bool) ($data['IsSupplier'] ?? false),
            isCustomer: ! (bool) ($data['IsSupplier'] ?? false),
            raw: $data,
        );
    }

    /**
     * Get the Exact Online URL for this account.
     */
    public function getUrl(string $baseUrl, string $division): string
    {
        return rtrim($baseUrl, '/')."/#/app/{$division}/Accounts/{$this->id}";
    }
}
