<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\PurchaseOrder;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreatePurchaseOrderAction
{
    use HandlesExactConnection;

    /**
     * Create a new purchase order in Exact Online.
     *
     * @param  array{
     *     Supplier: string,
     *     OrderDate?: string|null,
     *     ReceiptDate?: string|null,
     *     Description?: string|null,
     *     Currency?: string|null,
     *     YourRef?: string|null,
     *     Remarks?: string|null,
     *     Warehouse?: string|null,
     *     DropShipment?: bool|null,
     *     PurchaseOrderLines?: array<int, array{
     *         Item: string,
     *         Description?: string|null,
     *         Quantity: float,
     *         NetPrice?: float|null,
     *         ReceiptDate?: string|null,
     *         VATCode?: string|null
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
            $purchaseOrder = new PurchaseOrder($picqerConnection);

            foreach ($data as $key => $value) {
                $purchaseOrder->{$key} = $value;
            }

            $purchaseOrder->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created purchase order in Exact Online', [
                'connection_id' => $connection->id,
                'order_id' => $purchaseOrder->PurchaseOrderID,
                'order_number' => $purchaseOrder->OrderNumber ?? null,
            ]);

            return $purchaseOrder->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create purchase order in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to create purchase order: '.$e->getMessage(),
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
                'Supplier (Account ID) is required for purchase orders'
            );
        }
    }
}
