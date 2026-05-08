<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\SalesOrder;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class CreateSalesOrderAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Create a new sales order in Exact Online.
     *
     * @param  array<string, mixed>  $data  The entity data
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateCreatePayload('SalesOrder', $data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $salesOrder = new SalesOrder($picqerConnection);

            foreach ($data as $key => $value) {
                $salesOrder->{$key} = $value;
            }

            $salesOrder->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created sales order in Exact Online', [
                'connection_id' => $connection->id,
                'order_id' => $salesOrder->OrderID,
                'order_number' => $salesOrder->OrderNumber ?? null,
            ]);

            return $salesOrder->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create sales order in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to create sales order: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
