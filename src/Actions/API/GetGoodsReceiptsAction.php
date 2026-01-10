<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\GoodsReceipt;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetGoodsReceiptsAction
{
    use HandlesExactConnection;

    /**
     * Retrieve goods receipts from Exact Online.
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
            $goodsReceipt = new GoodsReceipt($picqerConnection);

            $this->applyQueryOptions($goodsReceipt, $options);

            $receipts = $goodsReceipt->get();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Retrieved goods receipts from Exact Online', [
                'connection_id' => $connection->id,
                'count' => count($receipts),
            ]);

            return collect($receipts)->map(fn ($r) => $r->attributes());

        } catch (\Exception $e) {
            Log::error('Failed to retrieve goods receipts from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve goods receipts: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function applyQueryOptions(GoodsReceipt $entity, array $options): void
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
