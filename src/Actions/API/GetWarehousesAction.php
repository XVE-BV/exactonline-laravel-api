<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Warehouse;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetWarehousesAction
{
    use HandlesExactConnection;

    /**
     * Retrieve warehouses from Exact Online.
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
            $warehouse = new Warehouse($picqerConnection);

            $this->applyQueryOptions($warehouse, $options);

            $warehouses = $warehouse->get();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Retrieved warehouses from Exact Online', [
                'connection_id' => $connection->id,
                'count' => count($warehouses),
            ]);

            return collect($warehouses)->map(fn ($w) => $w->attributes());

        } catch (\Exception $e) {
            Log::error('Failed to retrieve warehouses from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve warehouses: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function applyQueryOptions(Warehouse $entity, array $options): void
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
