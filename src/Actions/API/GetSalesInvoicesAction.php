<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\SalesInvoice;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class GetSalesInvoicesAction
{
    /**
     * Retrieve sales invoices from Exact Online
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array{
     *     filter?: string|null,
     *     select?: array<string>|null,
     *     expand?: array<string>|null,
     *     orderby?: string|null,
     *     top?: int|null,
     *     skip?: int|null,
     *     from_date?: string|null,
     *     to_date?: string|null,
     *     status?: int|null,
     *     customer?: string|null
     * }  $options  Query options for filtering invoices
     * @return Collection<int, array<string, mixed>>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $options = []): Collection
    {
        // Ensure we have a valid access token
        $this->ensureValidToken($connection);

        // Check rate limits before making the request
        $this->checkRateLimit($connection);

        try {
            // Get the picqer connection
            $picqerConnection = $connection->getPicqerConnection();

            // Create SalesInvoice instance
            $invoice = new SalesInvoice($picqerConnection);

            // Build filter from options
            $filter = $this->buildFilter($options);
            if (! empty($filter)) {
                $invoice->filter($filter);
            }

            // Apply other query options
            $this->applyQueryOptions($invoice, $options);

            // Get invoices
            $invoices = $invoice->get();

            // Track rate limit usage after the request
            $this->trackRateLimitUsage($connection, $picqerConnection);

            Log::info('Retrieved sales invoices from Exact Online', [
                'connection_id' => $connection->id,
                'count' => count($invoices),
                'filter' => $filter,
                'options' => $options,
            ]);

            // Convert to Laravel collection for easier manipulation
            return collect($invoices)->map(function ($invoice) {
                return $invoice->attributes();
            });

        } catch (\Exception $e) {
            Log::error('Failed to retrieve sales invoices from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'options' => $options,
            ]);

            throw new ConnectionException(
                'Failed to retrieve sales invoices: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Build OData filter string from options
     *
     * @param  array<string, mixed>  $options
     */
    protected function buildFilter(array $options): string
    {
        $filters = [];

        // Add custom filter if provided
        if (! empty($options['filter'])) {
            $filters[] = $options['filter'];
        }

        // Filter by date range
        if (! empty($options['from_date'])) {
            $filters[] = "InvoiceDate ge datetime'{$options['from_date']}'";
        }

        if (! empty($options['to_date'])) {
            $filters[] = "InvoiceDate le datetime'{$options['to_date']}'";
        }

        // Filter by status
        if (isset($options['status'])) {
            $filters[] = "Status eq {$options['status']}";
        }

        // Filter by customer
        if (! empty($options['customer'])) {
            $filters[] = "InvoiceTo eq guid'{$options['customer']}'";
        }

        // Combine filters with AND
        return ! empty($filters) ? implode(' and ', $filters) : '';
    }

    /**
     * Apply OData query options to the entity
     *
     * @param  SalesInvoice  $invoice
     * @param  array<string, mixed>  $options
     */
    protected function applyQueryOptions(SalesInvoice $invoice, array $options): void
    {
        // Apply select (field selection)
        if (! empty($options['select'])) {
            $invoice->select($options['select']);
        }

        // Apply expand (related entities)
        if (! empty($options['expand'])) {
            $invoice->expand(implode(',', $options['expand']));
        }

        // Apply orderby (default to InvoiceDate descending)
        $orderBy = $options['orderby'] ?? 'InvoiceDate desc';
        $invoice->orderBy($orderBy);

        // Apply top (limit)
        if (! empty($options['top'])) {
            $invoice->top($options['top']);
        }

        // Apply skip (offset)
        if (! empty($options['skip'])) {
            $invoice->skip($options['skip']);
        }
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
