<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\GoodsDelivery;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetGoodsDeliveriesAction
{
    use HandlesExactConnection;

    /**
     * Retrieve goods deliveries from Exact Online.
     *
     * @param  array<string, mixed>  $options  OData query options
     * @return Collection<int, array<string, mixed>>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $options = []): Collection
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $goodsDelivery = new GoodsDelivery($picqerConnection);

            $this->applyQueryOptions($goodsDelivery, $options);

            $deliveries = $goodsDelivery->get();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Retrieved goods deliveries from Exact Online', [
                'connection_id' => $connection->id,
                'count' => count($deliveries),
            ]);

            return collect($deliveries)->map(fn ($delivery) => $delivery->attributes());

        } catch (\Exception $e) {
            Log::error('Failed to retrieve goods deliveries from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve goods deliveries: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function applyQueryOptions(GoodsDelivery $entity, array $options): void
    {
        if (! empty($options['filter'])) {
            $entity->filter($options['filter']);
        }
        if (! empty($options['select'])) {
            $entity->select($options['select']);
        }
        if (! empty($options['expand'])) {
            $entity->expand(implode(',', $options['expand']));
        }
        if (! empty($options['top'])) {
            $entity->top($options['top']);
        }
    }
}
