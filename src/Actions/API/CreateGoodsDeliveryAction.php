<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\GoodsDelivery;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreateGoodsDeliveryAction
{
    use HandlesExactConnection;

    /**
     * Create a new goods delivery in Exact Online.
     *
     * @param  array{
     *     SalesOrderID?: string|null,
     *     DeliveryAccount?: string|null,
     *     DeliveryAddress?: string|null,
     *     DeliveryContact?: string|null,
     *     DeliveryDate?: string|null,
     *     Description?: string|null,
     *     ShippingMethod?: string|null,
     *     TrackingNumber?: string|null,
     *     Warehouse?: string|null,
     *     Remarks?: string|null,
     *     GoodsDeliveryLines?: array<int, array{
     *         SalesOrderLineID?: string|null,
     *         Item?: string|null,
     *         ItemDescription?: string|null,
     *         Quantity?: float|null,
     *         QuantityDelivered?: float|null,
     *         SerialNumber?: string|null,
     *         BatchNumber?: string|null,
     *         Notes?: string|null
     *     }>|null
     * }  $data
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $goodsDelivery = new GoodsDelivery($picqerConnection);

            foreach ($data as $key => $value) {
                $goodsDelivery->{$key} = $value;
            }

            $goodsDelivery->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created goods delivery in Exact Online', [
                'connection_id' => $connection->id,
                'delivery_id' => $goodsDelivery->EntryID,
                'delivery_number' => $goodsDelivery->DeliveryNumber ?? null,
            ]);

            return $goodsDelivery->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create goods delivery in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to create goods delivery: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
