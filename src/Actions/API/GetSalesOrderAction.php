<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\SalesOrder;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetSalesOrderAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single sales order from Exact Online.
     *
     * @param  string  $orderId  The Exact Online sales order ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $orderId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $salesOrder = new SalesOrder($picqerConnection);

            $result = $salesOrder->find($orderId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved sales order from Exact Online', [
                'connection_id' => $connection->id,
                'order_id' => $orderId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve sales order from Exact Online', [
                'connection_id' => $connection->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve sales order: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
