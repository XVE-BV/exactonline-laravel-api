<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Document;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class CreateDocumentAction
{
    use ValidatesPayload;

    /**
     * Create a new document in Exact Online
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array{
     *     Subject: string,
     *     Type?: int|null,
     *     Category?: string|null,
     *     Account?: string|null,
     *     Contact?: string|null,
     *     DocumentDate?: string|null,
     *     DocumentFolder?: string|null,
     *     FinancialTransactionEntryID?: string|null,
     *     HID?: int|null,
     *     Language?: string|null,
     *     Opportunity?: string|null,
     *     Project?: string|null,
     *     SalesInvoiceNumber?: int|null,
     *     SalesOrderNumber?: int|null,
     *     ShopOrderNumber?: int|null,
     *     TypeDescription?: string|null
     * }  $data  Document data following Exact Online's schema
     * @return array<string, mixed> The created document data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateCreatePayload('Document', $data);

        // Validate required fields
        $this->validateDocumentData($data);

        // Ensure we have a valid access token
        $this->ensureValidToken($connection);

        // Check rate limits before making the request
        $this->checkRateLimit($connection);

        try {
            // Get the picqer connection
            $picqerConnection = $connection->getPicqerConnection();

            // Create Document instance
            $document = new Document($picqerConnection);

            // Set document properties
            foreach ($data as $key => $value) {
                $document->{$key} = $value;
            }

            // Save the document
            $document->save();

            // Track rate limit usage after the request
            $this->trackRateLimitUsage($connection, $picqerConnection);

            Log::info('Created document in Exact Online', [
                'connection_id' => $connection->id,
                'document_id' => $document->ID,
                'document_subject' => $document->Subject,
                'document_type' => $document->Type ?? 'N/A',
            ]);

            // Return the created document data
            return $document->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create document in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new ConnectionException(
                'Failed to create document: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Validate required document data
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateDocumentData(array $data): void
    {
        // Subject is the only required field for documents
        if (empty($data['Subject'])) {
            throw ConnectionException::invalidConfiguration(
                'Document subject is required'
            );
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
