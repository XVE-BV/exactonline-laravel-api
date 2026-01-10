<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\PurchaseInvoice;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreatePurchaseInvoiceAction
{
    use HandlesExactConnection;

    /**
     * Create a new purchase invoice in Exact Online.
     *
     * @param  array{
     *     Supplier: string,
     *     InvoiceDate?: string|null,
     *     DueDate?: string|null,
     *     Description?: string|null,
     *     Currency?: string|null,
     *     YourRef?: string|null,
     *     Journal?: string|null,
     *     Document?: string|null,
     *     PaymentCondition?: string|null,
     *     PaymentReference?: string|null,
     *     Source?: int|null,
     *     Type?: int|null,
     *     PurchaseInvoiceLines?: array<int, array{
     *         Item?: string|null,
     *         GLAccount?: string|null,
     *         Description?: string|null,
     *         Quantity?: float|null,
     *         NetPrice?: float|null,
     *         Amount?: float|null,
     *         VATCode?: string|null,
     *         VATPercentage?: float|null,
     *         Project?: string|null,
     *         CostCenter?: string|null,
     *         CostUnit?: string|null
     *     }>|null
     * }  $data
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateData($data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $purchaseInvoice = new PurchaseInvoice($picqerConnection);

            foreach ($data as $key => $value) {
                $purchaseInvoice->{$key} = $value;
            }

            $purchaseInvoice->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created purchase invoice in Exact Online', [
                'connection_id' => $connection->id,
                'invoice_id' => $purchaseInvoice->ID,
                'invoice_number' => $purchaseInvoice->InvoiceNumber ?? null,
            ]);

            return $purchaseInvoice->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create purchase invoice in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to create purchase invoice: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateData(array $data): void
    {
        if (empty($data['Supplier'])) {
            throw ConnectionException::invalidConfiguration(
                'Supplier (Account ID) is required for purchase invoices'
            );
        }
    }
}
