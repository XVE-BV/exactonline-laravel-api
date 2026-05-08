<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\PurchaseOrder;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class GetPurchaseOrderAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single purchase order from Exact Online.
     *
     * @param  string  $orderId  The Exact Online purchase order ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $orderId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $purchaseOrder = new PurchaseOrder($picqerConnection);

            $result = $purchaseOrder->find($orderId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved purchase order from Exact Online', [
                'connection_id' => $connection->id,
                'order_id' => $orderId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve purchase order from Exact Online', [
                'connection_id' => $connection->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve purchase order: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
