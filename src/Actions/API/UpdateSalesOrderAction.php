<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\SalesOrder;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class UpdateSalesOrderAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

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
        $this->validateUpdatePayload('SalesOrder', $data);

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
