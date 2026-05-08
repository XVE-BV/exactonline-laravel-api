<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\PurchaseOrder;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class CreatePurchaseOrderAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Create a new purchase order in Exact Online.
     *
     * @param  array<string, mixed>  $data  The entity data
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateCreatePayload('PurchaseOrder', $data);
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
