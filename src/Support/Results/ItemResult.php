<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Support\Results;

/**
 * Value object representing an Exact Online Item (Product).
 */
final readonly class ItemResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public ?string $code,
        public string $description,
        public ?string $barcode,
        public ?float $costPrice,
        public ?float $salesPrice,
        public ?string $unit,
        public bool $isSalesItem,
        public bool $isPurchaseItem,
        public bool $isStockItem,
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
            description: $data['Description'] ?? '',
            barcode: $data['Barcode'] ?? null,
            costPrice: isset($data['CostPriceStandard']) ? (float) $data['CostPriceStandard'] : null,
            salesPrice: isset($data['SalesPrice']) ? (float) $data['SalesPrice'] : null,
            unit: $data['Unit'] ?? null,
            isSalesItem: (bool) ($data['IsSalesItem'] ?? false),
            isPurchaseItem: (bool) ($data['IsPurchaseItem'] ?? false),
            isStockItem: (bool) ($data['IsStockItem'] ?? false),
            raw: $data,
        );
    }

    /**
     * Get the Exact Online URL for this item.
     */
    public function getUrl(string $baseUrl, string $division): string
    {
        return rtrim($baseUrl, '/')."/#/app/{$division}/Items/{$this->id}";
    }
}
