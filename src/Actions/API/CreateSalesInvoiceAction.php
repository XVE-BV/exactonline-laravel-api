<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\SalesInvoice;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class CreateSalesInvoiceAction
{
    /**
     * Create a new sales invoice in Exact Online
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array{
     *     InvoiceTo: string,
     *     OrderedBy?: string|null,
     *     InvoiceDate?: string|null,
     *     DueDate?: string|null,
     *     Currency?: string|null,
     *     Description?: string|null,
     *     PaymentCondition?: string|null,
     *     PaymentReference?: string|null,
     *     Journal?: string|null,
     *     Status?: int|null,
     *     Type?: int|null,
     *     Remarks?: string|null,
     *     InvoiceNumber?: int|null,
     *     DeliverTo?: string|null,
     *     DeliveryAddress?: string|null,
     *     DeliveryDate?: string|null,
     *     OrderNumber?: int|null,
     *     OrderDate?: string|null,
     *     SalesInvoiceLines: array<array{
     *         Item?: string|null,
     *         Quantity?: float|null,
     *         UnitPrice?: float|null,
     *         VATAmount?: float|null,
     *         VATCode?: string|null,
     *         Description?: string|null,
     *         Discount?: float|null,
     *         LineNumber?: int|null,
     *         GLAccount?: string|null,
     *         CostCenter?: string|null,
     *         CostUnit?: string|null,
     *         Project?: string|null,
     *         Subscription?: string|null,
     *         NetPrice?: float|null
     *     }>
     * }  $invoiceData  Invoice data following Exact Online's schema
     * @return array<string, mixed> The created invoice data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $invoiceData): array
    {
        // Validate required fields
        $this->validateInvoiceData($invoiceData);

        // Ensure we have a valid access token
        $this->ensureValidToken($connection);

        // Check rate limits before making the request
        $this->checkRateLimit($connection);

        try {
            // Get the picqer connection
            $picqerConnection = $connection->getPicqerConnection();

            // Create SalesInvoice instance
            $invoice = new SalesInvoice($picqerConnection);

            // Set invoice properties (excluding lines)
            $lines = $invoiceData['SalesInvoiceLines'] ?? [];
            unset($invoiceData['SalesInvoiceLines']);

            foreach ($invoiceData as $key => $value) {
                $invoice->{$key} = $value;
            }

            // Add invoice lines
            if (! empty($lines)) {
                $invoice->SalesInvoiceLines = $this->prepareSalesInvoiceLines($lines);
            }

            // Save the invoice
            $invoice->save();

            // Track rate limit usage after the request
            $this->trackRateLimitUsage($connection, $picqerConnection);

            Log::info('Created sales invoice in Exact Online', [
                'connection_id' => $connection->id,
                'invoice_id' => $invoice->InvoiceID,
                'invoice_number' => $invoice->InvoiceNumber ?? 'N/A',
                'invoice_to' => $invoice->InvoiceTo,
                'amount' => $invoice->AmountDC ?? 0,
            ]);

            // Return the created invoice data
            return $invoice->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create sales invoice in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'invoice_data' => $invoiceData,
            ]);

            throw new ConnectionException(
                'Failed to create sales invoice: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Validate required invoice data
     *
     * @param  array<string, mixed>  $invoiceData
     *
     * @throws ConnectionException
     */
    protected function validateInvoiceData(array $invoiceData): void
    {
        // InvoiceTo is required
        if (empty($invoiceData['InvoiceTo'])) {
            throw ConnectionException::invalidConfiguration(
                'InvoiceTo (customer account ID) is required for creating an invoice'
            );
        }

        // At least one invoice line is recommended
        if (empty($invoiceData['SalesInvoiceLines'])) {
            Log::warning('Creating invoice without lines', [
                'invoice_to' => $invoiceData['InvoiceTo'],
            ]);
        }

        // Validate invoice lines if provided
        if (! empty($invoiceData['SalesInvoiceLines'])) {
            foreach ($invoiceData['SalesInvoiceLines'] as $index => $line) {
                // Either Item or Description should be present
                if (empty($line['Item']) && empty($line['Description'])) {
                    throw ConnectionException::invalidConfiguration(
                        "Invoice line {$index} must have either an Item ID or Description"
                    );
                }
            }
        }
    }

    /**
     * Prepare sales invoice lines for submission
     *
     * @param  array<array<string, mixed>>  $lines
     * @return array<array<string, mixed>>
     */
    protected function prepareSalesInvoiceLines(array $lines): array
    {
        $preparedLines = [];

        foreach ($lines as $lineData) {
            // Create a simple array with line data
            // The picqer library will handle converting this to the proper format
            $preparedLine = [];

            foreach ($lineData as $key => $value) {
                if ($value !== null) {
                    $preparedLine[$key] = $value;
                }
            }

            $preparedLines[] = $preparedLine;
        }

        return $preparedLines;
    }

    /**
     * Ensure the connection has a valid access token
     */
    protected function ensureValidToken(ExactConnection $connection): void
    {
        if ($this->tokenNeedsRefresh($connection)) {
            $refreshAction = Config::getAction(
                'refresh_access_token',
                RefreshAccessTokenAction::class
            );
            $refreshAction->execute($connection);

            // Refresh the connection to get updated tokens
            $connection->refresh();
        }
    }

    /**
     * Check if token needs refresh (proactive at 9 minutes)
     */
    protected function tokenNeedsRefresh(ExactConnection $connection): bool
    {
        if (empty($connection->token_expires_at)) {
            return true;
        }

        // Refresh proactively at 9 minutes (540 seconds before expiry)
        return $connection->token_expires_at < (now()->timestamp + 540);
    }

    /**
     * Check rate limits before making the API request
     */
    protected function checkRateLimit(ExactConnection $connection): void
    {
        $checkRateLimitAction = Config::getAction(
            'check_rate_limit',
            CheckRateLimitAction::class
        );
        $checkRateLimitAction->execute($connection);
    }

    /**
     * Track rate limit usage after the API request
     */
    protected function trackRateLimitUsage(ExactConnection $connection, \Picqer\Financials\Exact\Connection $picqerConnection): void
    {
        $trackRateLimitAction = Config::getAction(
            'track_rate_limit_usage',
            TrackRateLimitUsageAction::class
        );
        $trackRateLimitAction->execute($connection, $picqerConnection);
    }
}
