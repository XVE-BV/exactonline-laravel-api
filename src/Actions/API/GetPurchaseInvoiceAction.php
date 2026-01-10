<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\PurchaseInvoice;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetPurchaseInvoiceAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single purchase invoice from Exact Online.
     *
     * @param  string  $invoiceId  The Exact Online purchase invoice ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $invoiceId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $purchaseInvoice = new PurchaseInvoice($picqerConnection);

            $result = $purchaseInvoice->find($invoiceId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved purchase invoice from Exact Online', [
                'connection_id' => $connection->id,
                'invoice_id' => $invoiceId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve purchase invoice from Exact Online', [
                'connection_id' => $connection->id,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve purchase invoice: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
