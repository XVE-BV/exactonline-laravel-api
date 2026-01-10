<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Support\Results;

use Illuminate\Support\Carbon;

/**
 * Value object representing an Exact Online Sales Invoice.
 */
final readonly class SalesInvoiceResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public ?string $invoiceNumber,
        public ?string $orderId,
        public ?string $accountId,
        public ?string $accountCode,
        public ?string $accountName,
        public ?float $amountDC,
        public ?float $amountFC,
        public ?string $currency,
        public ?Carbon $invoiceDate,
        public ?Carbon $dueDate,
        public ?string $status,
        public ?string $description,
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
            id: $data['InvoiceID'] ?? $data['ID'] ?? '',
            invoiceNumber: $data['InvoiceNumber'] ?? null,
            orderId: $data['OrderID'] ?? null,
            accountId: $data['InvoiceTo'] ?? $data['OrderedBy'] ?? null,
            accountCode: $data['InvoiceToContactPersonFullName'] ?? null,
            accountName: $data['InvoiceToName'] ?? $data['OrderedByName'] ?? null,
            amountDC: isset($data['AmountDC']) ? (float) $data['AmountDC'] : null,
            amountFC: isset($data['AmountFC']) ? (float) $data['AmountFC'] : null,
            currency: $data['Currency'] ?? null,
            invoiceDate: isset($data['InvoiceDate']) ? Carbon::parse($data['InvoiceDate']) : null,
            dueDate: isset($data['DueDate']) ? Carbon::parse($data['DueDate']) : null,
            status: $data['Status'] ?? null,
            description: $data['Description'] ?? null,
            raw: $data,
        );
    }

    /**
     * Get the Exact Online URL for this invoice.
     */
    public function getUrl(string $baseUrl, string $division): string
    {
        return rtrim($baseUrl, '/')."/#/app/{$division}/SalesInvoices/{$this->id}";
    }
}
