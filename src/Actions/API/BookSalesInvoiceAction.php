<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\PrintedSalesInvoice;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

/**
 * Book (print) a draft sales invoice in Exact Online.
 *
 * Exact's PrintedSalesInvoices endpoint finalises the draft identified by
 * $invoiceId, which is what assigns its definitive InvoiceNumber. The POST
 * response itself does NOT carry that number — re-read the sales invoice
 * afterwards (GetSalesInvoiceAction) to pull the InvoiceNumber back.
 */
class BookSalesInvoiceAction
{
    use HandlesExactConnection;

    /**
     * @param  string  $invoiceId  The Exact Online sales invoice ID (GUID) of the draft to book.
     * @param  array<string, mixed>  $options  Overrides merged into the POST body (e.g. DocumentLayout,
     *                                         InvoiceDate). The Send* flags default to false so booking
     *                                         never auto-emails / auto-Peppols / posts the invoice.
     * @return array<string, mixed> The PrintedSalesInvoice attributes returned by Exact.
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $invoiceId, array $options = []): array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $printed = $this->makePrintedSalesInvoice($picqerConnection);

            $printed->InvoiceID = $invoiceId;
            $printed->SendEmailToCustomer = false;
            $printed->SendInvoiceToCustomerPostbox = false;
            $printed->SendInvoiceViaPeppol = false;
            $printed->SendOutputBasedOnAccount = false;

            foreach ($options as $key => $value) {
                $printed->{$key} = $value;
            }

            $printed->save();

            $this->completeRequest($connection, $picqerConnection);

            $attributes = $printed->attributes();

            // Exact returns HTTP 200 even when the document/booking itself failed;
            // the failure surfaces in DocumentCreationError. Treat that as a hard
            // error so the caller never records a "booked" invoice that Exact did
            // not actually finalise.
            $documentError = $attributes['DocumentCreationError'] ?? null;
            if (! empty($documentError)) {
                throw new \RuntimeException('Exact reported a document creation error while booking: '.$documentError);
            }

            Log::info('Booked sales invoice in Exact Online', [
                'connection_id' => $connection->id,
                'invoice_id' => $invoiceId,
            ]);

            return $attributes;

        } catch (\Exception $e) {
            Log::error('Failed to book sales invoice in Exact Online', [
                'connection_id' => $connection->id,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to book sales invoice: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Seam for tests: builds the picqer PrintedSalesInvoice entity.
     */
    protected function makePrintedSalesInvoice(Connection $connection): PrintedSalesInvoice
    {
        return new PrintedSalesInvoice($connection);
    }
}
