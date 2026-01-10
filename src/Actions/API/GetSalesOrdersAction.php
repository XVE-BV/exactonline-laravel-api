<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\SalesOrder;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetSalesOrdersAction
{
    use HandlesExactConnection;

    /**
     * Retrieve sales orders from Exact Online.
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
            $salesOrder = new SalesOrder($picqerConnection);

            $this->applyQueryOptions($salesOrder, $options);

            $orders = $salesOrder->get();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Retrieved sales orders from Exact Online', [
                'connection_id' => $connection->id,
                'count' => count($orders),
            ]);

            return collect($orders)->map(fn ($order) => $order->attributes());

        } catch (\Exception $e) {
            Log::error('Failed to retrieve sales orders from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve sales orders: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function applyQueryOptions(SalesOrder $entity, array $options): void
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
