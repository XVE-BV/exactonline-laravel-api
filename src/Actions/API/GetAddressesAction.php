<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Address;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetAddressesAction
{
    use HandlesExactConnection;

    /**
     * Retrieve addresses from Exact Online.
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
            $address = new Address($picqerConnection);

            $this->applyQueryOptions($address, $options);

            $addresses = $address->get();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Retrieved addresses from Exact Online', [
                'connection_id' => $connection->id,
                'count' => count($addresses),
            ]);

            return collect($addresses)->map(fn ($a) => $a->attributes());

        } catch (\Exception $e) {
            Log::error('Failed to retrieve addresses from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve addresses: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function applyQueryOptions(Address $entity, array $options): void
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
