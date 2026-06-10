<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\Document;
use Picqer\Financials\Exact\DocumentAttachment;
use XVE\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use XVE\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use XVE\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;
use XVE\ExactonlineLaravelApi\Support\Config;

class DownloadDocumentAction
{
    /**
     * Download a document attachment from Exact Online
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $documentId  The document ID (GUID)
     * @param  string|null  $attachmentId  The attachment ID (GUID) - if null, downloads the first attachment
     * @return array{
     *     content?: string,
     *     filename: string,
     *     mime_type: string,
     *     size: int,
     *     attachment_id: string
     * }|null Returns document data or null if not found
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $documentId, ?string $attachmentId = null, bool $includeContent = true): ?array
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

            if (! $result->exists()) {
                Log::info('Document attachment not found', [
                    'connection_id' => $connection->id,
                    'document_id' => $documentId,
                    'attachment_id' => $attachmentId,
                ]);

                return null;
            }

            $filename = $result->FileName ?? 'document.pdf';
            $size = (int) ($result->FileSize ?? 0);

            $data = [
                'filename' => $filename,
                'mime_type' => $this->getMimeType($filename),
                'size' => $size,
                'attachment_id' => (string) ($result->ID ?? $attachmentId),
            ];

            if ($includeContent) {
                $content = $this->downloadFile($result);
                $data['content'] = $content;
                $data['size'] = strlen($content);
            }

            // Track rate limit usage after Exact requests have completed.
            $this->trackRateLimitUsage($connection, $picqerConnection);

            Log::info('Retrieved document attachment from Exact Online', [
                'connection_id' => $connection->id,
                'document_id' => $documentId,
                'attachment_id' => $attachmentId,
                'filename' => $filename,
                'content_included' => $includeContent,
                'size' => $data['size'],
            ]);

            return $data;

        } catch (\Throwable $e) {
            Log::error('Failed to download document from Exact Online', [
                'connection_id' => $connection->id,
                'document_id' => $documentId,
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to download document: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get the first attachment ID for a document
     */
    protected function getFirstAttachmentId(ExactConnection $connection, string $documentId): ?string
    {
        $picqerConnection = $connection->getPicqerConnection();

        $document = new Document($picqerConnection);
        $doc = $document->find($documentId);

        if (! $doc->exists()) {
            return null;
        }

        $attachments = new DocumentAttachment($picqerConnection);
        $attachmentList = $attachments->filter("Document eq guid'{$documentId}'");

        if (empty($attachmentList)) {
            return null;
        }

        return $attachmentList[0]->ID;
    }

    /**
     * Download file content using Picqer's documented Downloadable trait.
     *
     * @throws \Throwable
     */
    protected function downloadFile(DocumentAttachment $attachment): string
    {
        $content = $attachment->download()->getContents();

        if ($content === '') {
            throw new \RuntimeException('Downloaded file is empty');
        }

        return $content;
    }


    /**
     * Get MIME type based on file extension
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
        return $connection->token_expires_at < (now()->getTimestamp() + 540);
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
    protected function trackRateLimitUsage(ExactConnection $connection, Connection $picqerConnection): void
    {
        $trackRateLimitAction = Config::getAction(
            'track_rate_limit_usage',
            TrackRateLimitUsageAction::class
        );
        $trackRateLimitAction->execute($connection, $picqerConnection);
    }
}
