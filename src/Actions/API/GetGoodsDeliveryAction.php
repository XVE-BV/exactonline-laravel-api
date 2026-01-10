<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\GoodsDelivery;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetGoodsDeliveryAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single goods delivery from Exact Online.
     *
     * @param  string  $deliveryId  The Exact Online goods delivery ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $deliveryId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $goodsDelivery = new GoodsDelivery($picqerConnection);

            $result = $goodsDelivery->find($deliveryId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved goods delivery from Exact Online', [
                'connection_id' => $connection->id,
                'delivery_id' => $deliveryId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve goods delivery from Exact Online', [
                'connection_id' => $connection->id,
                'delivery_id' => $deliveryId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve goods delivery: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
