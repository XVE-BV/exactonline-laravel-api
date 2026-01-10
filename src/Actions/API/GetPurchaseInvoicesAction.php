<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\PurchaseInvoice;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetPurchaseInvoicesAction
{
    use HandlesExactConnection;

    /**
     * Retrieve purchase invoices from Exact Online.
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
            $purchaseInvoice = new PurchaseInvoice($picqerConnection);

            $this->applyQueryOptions($purchaseInvoice, $options);

            $invoices = $purchaseInvoice->get();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Retrieved purchase invoices from Exact Online', [
                'connection_id' => $connection->id,
                'count' => count($invoices),
            ]);

            return collect($invoices)->map(fn ($i) => $i->attributes());

        } catch (\Exception $e) {
            Log::error('Failed to retrieve purchase invoices from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve purchase invoices: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function applyQueryOptions(PurchaseInvoice $entity, array $options): void
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
