<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\SalesInvoice;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetSalesInvoiceAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single sales invoice from Exact Online.
     *
     * @param  string  $invoiceId  The Exact Online sales invoice ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $invoiceId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $salesInvoice = new SalesInvoice($picqerConnection);

            $result = $salesInvoice->find($invoiceId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved sales invoice from Exact Online', [
                'connection_id' => $connection->id,
                'invoice_id' => $invoiceId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve sales invoice from Exact Online', [
                'connection_id' => $connection->id,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve sales invoice: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
