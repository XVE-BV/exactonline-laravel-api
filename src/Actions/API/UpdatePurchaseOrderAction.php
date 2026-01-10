<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\PurchaseOrder;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class UpdatePurchaseOrderAction
{
    use HandlesExactConnection;

    /**
     * Update an existing purchase order in Exact Online.
     *
     * @param  string  $orderId  The Exact Online purchase order ID (GUID)
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $orderId, array $data): array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $purchaseOrder = new PurchaseOrder($picqerConnection);
            $purchaseOrder->PurchaseOrderID = $orderId;

            foreach ($data as $key => $value) {
                $purchaseOrder->{$key} = $value;
            }

            $purchaseOrder->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Updated purchase order in Exact Online', [
                'connection_id' => $connection->id,
                'order_id' => $orderId,
            ]);

            return $purchaseOrder->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to update purchase order in Exact Online', [
                'connection_id' => $connection->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to update purchase order: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
