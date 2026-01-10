<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\PurchaseOrder;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetPurchaseOrdersAction
{
    use HandlesExactConnection;

    /**
     * Retrieve purchase orders from Exact Online.
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
            $purchaseOrder = new PurchaseOrder($picqerConnection);

            $this->applyQueryOptions($purchaseOrder, $options);

            $orders = $purchaseOrder->get();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Retrieved purchase orders from Exact Online', [
                'connection_id' => $connection->id,
                'count' => count($orders),
            ]);

            return collect($orders)->map(fn ($o) => $o->attributes());

        } catch (\Exception $e) {
            Log::error('Failed to retrieve purchase orders from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve purchase orders: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function applyQueryOptions(PurchaseOrder $entity, array $options): void
    {
        if (! empty($options['filter'])) {
            $entity->filter($options['filter']);
        }
        if (! empty($options['select'])) {
            $entity->select($options['select']);
        }
        if (! empty($options['top'])) {
            $entity->top($options['top']);
        }
    }
}
