<?php

declare(strict_types=1);

use Laravel\Mcp\Server\McpServiceProvider;
use Picqer\Financials\Exact\DocumentAttachment;
use Psr\Http\Message\StreamInterface;
use XVE\ExactonlineLaravelApi\Actions\API\DownloadDocumentAction;
use XVE\ExactonlineLaravelApi\Mcp\ExactMcpServer;
use XVE\ExactonlineLaravelApi\Mcp\Tools\DownloadDocumentTool;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class FakeMcpDownloadDocumentAction extends DownloadDocumentAction
{
    public bool $includeContent = true;

    public function execute(ExactConnection $connection, string $documentId, ?string $attachmentId = null, bool $includeContent = true): ?array
    {
        $this->includeContent = $includeContent;

        $result = [
            'filename' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 7,
            'attachment_id' => 'attachment-1',
        ];

        if ($includeContent) {
            $result['content'] = 'content';
        }

        return $result;
    }
}

class ThrowableMcpDownloadDocumentAction extends DownloadDocumentAction
{
    public function execute(ExactConnection $connection, string $documentId, ?string $attachmentId = null, bool $includeContent = true): ?array
    {
        throw new Error('missing client /private/path/Connection.php:157');
    }
}

class ExposedDownloadDocumentAction extends DownloadDocumentAction
{
    public function exposedDownloadFile(DocumentAttachment $attachment): string
    {
        return $this->downloadFile($attachment);
    }
}

beforeEach(function () {
    $this->app->register(McpServiceProvider::class);

    ExactConnection::factory()->create([
        'name' => 'MCP test connection',
        'is_active' => true,
        'last_used_at' => now(),
        'access_token' => 'access',
        'refresh_token' => 'refresh',
        'token_expires_at' => now()->addHour()->timestamp,
    ]);
});

it('returns metadata without requesting binary content by default', function () {
    $action = new FakeMcpDownloadDocumentAction;

    $this->app->instance(FakeMcpDownloadDocumentAction::class, $action);
    config()->set('exactonline-laravel-api.actions.download_document', FakeMcpDownloadDocumentAction::class);

    ExactMcpServer::tool(DownloadDocumentTool::class, [
        'document_id' => 'f81ae219-05fe-40ee-9914-234abff670c5',
    ])->assertOk()->assertSee('"_content_omitted":true')->assertDontSee('content_base64');

    expect($action->includeContent)->toBeFalse();
});

it('includes base64 content only when requested', function () {
    $action = new FakeMcpDownloadDocumentAction;

    $this->app->instance(FakeMcpDownloadDocumentAction::class, $action);
    config()->set('exactonline-laravel-api.actions.download_document', FakeMcpDownloadDocumentAction::class);

    ExactMcpServer::tool(DownloadDocumentTool::class, [
        'document_id' => 'f81ae219-05fe-40ee-9914-234abff670c5',
        'include_content' => true,
    ])->assertOk()->assertSee('"content_base64":"Y29udGVudA=="');

    expect($action->includeContent)->toBeTrue();
});

it('sanitizes throwable failures from document download', function () {
    config()->set('exactonline-laravel-api.actions.download_document', ThrowableMcpDownloadDocumentAction::class);

    ExactMcpServer::tool(DownloadDocumentTool::class, [
        'document_id' => 'f81ae219-05fe-40ee-9914-234abff670c5',
        'include_content' => true,
    ])->assertHasErrors()->assertSee('Download failed.')->assertDontSee('/private/path');
});

it('downloads document bytes through the Picqer attachment download stream', function () {
    $stream = Mockery::mock(StreamInterface::class);
    $stream->shouldReceive('getContents')->once()->andReturn('binary-data');

    $attachment = Mockery::mock(DocumentAttachment::class);
    $attachment->shouldReceive('download')->once()->andReturn($stream);

    expect((new ExposedDownloadDocumentAction)->exposedDownloadFile($attachment))->toBe('binary-data');
});
