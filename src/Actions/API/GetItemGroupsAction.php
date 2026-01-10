<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\ItemGroup;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetItemGroupsAction
{
    use HandlesExactConnection;

    /**
     * Retrieve item groups from Exact Online.
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
            $itemGroup = new ItemGroup($picqerConnection);

            $this->applyQueryOptions($itemGroup, $options);

            $itemGroups = $itemGroup->get();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Retrieved item groups from Exact Online', [
                'connection_id' => $connection->id,
                'count' => count($itemGroups),
            ]);

            return collect($itemGroups)->map(fn ($i) => $i->attributes());

        } catch (\Exception $e) {
            Log::error('Failed to retrieve item groups from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve item groups: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function applyQueryOptions(ItemGroup $entity, array $options): void
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
