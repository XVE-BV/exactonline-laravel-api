<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\SalesInvoiceLine;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

/**
 * Retrieve the SalesInvoiceLines of a single Exact Online sales invoice.
 *
 * Exact returns invoice lines as a separate resource (a deferred navigation
 * property on the invoice header), so GetSalesInvoiceAction does not expose
 * them. This action fetches them directly, ordered by LineNumber, for callers
 * that need to compare lines (e.g. the pre-booking equality guard).
 */
class GetSalesInvoiceLinesAction
{
    use HandlesExactConnection;

    /**
     * @param  string  $invoiceId  The Exact Online sales invoice ID (GUID).
     * @return array<int, array<string, mixed>> The line attributes, ordered by LineNumber.
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $invoiceId): array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $line = $this->makeSalesInvoiceLine($picqerConnection);

            $results = $line->filter(
                "InvoiceID eq guid'".$invoiceId."'",
                '',
                '',
                ['$orderby' => 'LineNumber']
            );

            $this->completeRequest($connection, $picqerConnection);

            $lines = collect($results)
                ->map(static fn ($entity) => $entity->attributes())
                ->all();

            Log::info('Retrieved sales invoice lines from Exact Online', [
                'connection_id' => $connection->id,
                'invoice_id' => $invoiceId,
                'count' => count($lines),
            ]);

            return $lines;

        } catch (\Exception $e) {
            Log::error('Failed to retrieve sales invoice lines from Exact Online', [
                'connection_id' => $connection->id,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve sales invoice lines: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Seam for tests: builds the picqer SalesInvoiceLine entity.
     */
    protected function makeSalesInvoiceLine(Connection $connection): SalesInvoiceLine
    {
        return new SalesInvoiceLine($connection);
    }
}
