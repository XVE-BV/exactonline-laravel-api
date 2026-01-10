<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\SalesOrder;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class UpdateSalesOrderAction
{
    use HandlesExactConnection;

    /**
     * Update an existing sales order in Exact Online.
     *
     * @param  string  $orderId  The Exact Online order ID (GUID)
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $orderId, array $data): array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $salesOrder = new SalesOrder($picqerConnection);
            $salesOrder->OrderID = $orderId;

            foreach ($data as $key => $value) {
                $salesOrder->{$key} = $value;
            }

            $salesOrder->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Updated sales order in Exact Online', [
                'connection_id' => $connection->id,
                'order_id' => $orderId,
            ]);

            return $salesOrder->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to update sales order in Exact Online', [
                'connection_id' => $connection->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to update sales order: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
