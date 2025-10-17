<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Document;
use Picqer\Financials\Exact\DocumentAttachment;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class DownloadDocumentAction
{
    /**
     * Download a document attachment from Exact Online
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $documentId  The document ID (GUID)
     * @param  string|null  $attachmentId  The attachment ID (GUID) - if null, downloads the first attachment
     * @return array{
     *     content: string,
     *     filename: string,
     *     mime_type: string,
     *     size: int
     * }|null Returns document data or null if not found
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $documentId, ?string $attachmentId = null): ?array
    {
        // Ensure we have a valid access token
        $this->ensureValidToken($connection);

        // Check rate limits before making the request
        $this->checkRateLimit($connection);

        try {
            // Get the picqer connection
            $picqerConnection = $connection->getPicqerConnection();

            // If no attachment ID provided, get the document's attachments
            if ($attachmentId === null) {
                $attachmentId = $this->getFirstAttachmentId($connection, $documentId);
                
                if ($attachmentId === null) {
                    Log::info('No attachments found for document', [
                        'connection_id' => $connection->id,
                        'document_id' => $documentId,
                    ]);

                    return null;
                }
            }

            // Download the attachment
            $attachment = new DocumentAttachment($picqerConnection);
            $result = $attachment->find($attachmentId);

            if ($result === null) {
                Log::info('Document attachment not found', [
                    'connection_id' => $connection->id,
                    'document_id' => $documentId,
                    'attachment_id' => $attachmentId,
                ]);

                return null;
            }

            // Download the actual file content
            $downloadUrl = $result->Url;
            
            if (empty($downloadUrl)) {
                throw new ConnectionException('Document attachment has no download URL');
            }

            // Use picqer connection to download the file
            $content = $this->downloadFile($picqerConnection, $downloadUrl);

            // Track rate limit usage after the request
            $this->trackRateLimitUsage($connection, $picqerConnection);

            Log::info('Downloaded document from Exact Online', [
                'connection_id' => $connection->id,
                'document_id' => $documentId,
                'attachment_id' => $attachmentId,
                'filename' => $result->FileName ?? 'unknown',
                'size' => strlen($content),
            ]);

            return [
                'content' => $content,
                'filename' => $result->FileName ?? 'document.pdf',
                'mime_type' => $this->getMimeType($result->FileName ?? ''),
                'size' => strlen($content),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to download document from Exact Online', [
                'connection_id' => $connection->id,
                'document_id' => $documentId,
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to download document: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get the first attachment ID for a document
     *
     * @param  ExactConnection  $connection
     * @param  string  $documentId
     * @return string|null
     */
    protected function getFirstAttachmentId(ExactConnection $connection, string $documentId): ?string
    {
        $picqerConnection = $connection->getPicqerConnection();

        // Get the document
        $document = new Document($picqerConnection);
        $doc = $document->find($documentId);

        if ($doc === null) {
            return null;
        }

        // Get attachments
        $attachments = new DocumentAttachment($picqerConnection);
        $attachments->filter("Document eq guid'{$documentId}'");
        $attachmentList = $attachments->get();

        if (empty($attachmentList)) {
            return null;
        }

        // Return the first attachment ID
        return $attachmentList[0]->ID;
    }

    /**
     * Download file content from URL using picqer connection
     *
     * @param  \Picqer\Financials\Exact\Connection  $picqerConnection
     * @param  string  $url
     * @return string
     *
     * @throws \Exception
     */
    protected function downloadFile(\Picqer\Financials\Exact\Connection $picqerConnection, string $url): string
    {
        // Use picqer's download method if available
        // Otherwise, make a direct HTTP request with authentication
        $client = $picqerConnection->getClient();
        
        $response = $client->request('GET', $url, [
            'headers' => [
                'Accept' => 'application/octet-stream',
                'Authorization' => 'Bearer ' . $picqerConnection->getAccessToken(),
            ],
        ]);

        $content = $response->getBody()->getContents();

        if (empty($content)) {
            throw new \Exception('Downloaded file is empty');
        }

        return $content;
    }

    /**
     * Get MIME type based on file extension
     *
     * @param  string  $filename
     * @return string
     */
    protected function getMimeType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'txt' => 'text/plain',
            'xml' => 'application/xml',
            'json' => 'application/json',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
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
