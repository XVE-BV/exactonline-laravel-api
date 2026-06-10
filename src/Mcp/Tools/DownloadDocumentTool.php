<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use XVE\ExactonlineLaravelApi\Actions\API\DownloadDocumentAction;
use XVE\ExactonlineLaravelApi\Mcp\Support\ConnectionResolver;
use XVE\ExactonlineLaravelApi\Support\Config;

#[IsReadOnly]
class DownloadDocumentTool extends Tool
{
    use HandlesMcpToolErrors;

    protected string $name = 'exact_document_download';

    protected string $description = 'Download an Exact Online document by GUID. Always returns document metadata. Base64 file content is only included when include_content=true and the file is within the max_document_bytes limit.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'document_id' => $schema->string()
                ->description('GUID of the document to download.'),
            'include_content' => $schema->boolean()
                ->description('Whether to include the base64-encoded file content. Default: false.')
                ->default(false),
            'connection_id' => $schema->integer()
                ->description('ID of the connection to use. Defaults to the most recently used active connection.'),
            'connection_name' => $schema->string()
                ->description('Name of the connection. Alternative to connection_id.'),
        ];
    }

    public function handle(Request $request): Response
    {
        return $this->safely(function () use ($request): Response {
            $documentId = $request->get('document_id');

            if (empty($documentId)) {
                return Response::error('document_id is required.');
            }

            try {
                $connection = (new ConnectionResolver)->resolve($request->all());
            } catch (\Throwable $e) {
                return Response::error($e->getMessage());
            }

            $includeContent = (bool) $request->get('include_content');

            try {
                $action = Config::getAction('download_document', DownloadDocumentAction::class);
                $result = $action->execute($connection, (string) $documentId, null, $includeContent);
            } catch (\Throwable $e) {
                return Response::error('Download failed.');
            }

            if ($result === null) {
                return Response::error("Document {$documentId} not found.");
            }

            $meta = [
                'document_id' => $documentId,
                'attachment_id' => $result['attachment_id'],
                'filename' => $result['filename'],
                'mime_type' => $result['mime_type'],
                'size' => $result['size'],
            ];

            if (! $includeContent) {
                return Response::json(array_merge($meta, ['_content_omitted' => true]));
            }

            $maxBytes = (int) config('exactonline-laravel-api.mcp.limits.max_document_bytes', 5 * 1024 * 1024);

            if ($result['size'] > $maxBytes) {
                return Response::json(array_merge($meta, [
                    '_content_omitted' => true,
                    '_content_size_bytes' => $result['size'],
                    '_content_limit_bytes' => $maxBytes,
                ]));
            }

            return Response::json(array_merge($meta, [
                'content_base64' => base64_encode((string) ($result['content'] ?? '')),
            ]));
        });
    }
}
