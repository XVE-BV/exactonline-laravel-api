<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\SalesOrder;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreateSalesOrderAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Create a new sales order in Exact Online.
     *
     * @param  array{
     *     OrderedBy: string,
     *     OrderDate?: string|null,
     *     DeliveryDate?: string|null,
     *     Description?: string|null,
     *     Currency?: string|null,
     *     YourRef?: string|null,
     *     Remarks?: string|null,
     *     DeliveryAddress?: string|null,
     *     InvoiceTo?: string|null,
     *     SalesOrderLines?: array<int, array{
     *         Item: string,
     *         Description?: string|null,
     *         Quantity: float,
     *         UnitPrice?: float|null,
     *         Discount?: float|null,
     *         VATCode?: string|null,
     *         Notes?: string|null
     *     }>|null
     * }  $data
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
