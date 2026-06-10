<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use XVE\ExactonlineLaravelApi\Mcp\ExactMcpServer;
use XVE\ExactonlineLaravelApi\Mcp\Tools\DownloadDocumentTool;
use XVE\ExactonlineLaravelApi\Mcp\Tools\ExactApiReadTool;
use XVE\ExactonlineLaravelApi\Mcp\Tools\GetConnectionTool;
use XVE\ExactonlineLaravelApi\Mcp\Tools\GetLocalRecordTool;
use XVE\ExactonlineLaravelApi\Mcp\Tools\ListConnectionsTool;
use XVE\ExactonlineLaravelApi\Mcp\Tools\ListLocalRecordsTool;
use XVE\ExactonlineLaravelApi\Mcp\Tools\TokenStatusTool;

function exactMcpToolClasses(): array
{
    $reflection = new ReflectionClass(ExactMcpServer::class);

    return $reflection->getDefaultProperties()['tools'];
}

it('registers exactly the seven read-only MCP tools', function () {
    expect(exactMcpToolClasses())->toBe([
        ListConnectionsTool::class,
        GetConnectionTool::class,
        ListLocalRecordsTool::class,
        GetLocalRecordTool::class,
        ExactApiReadTool::class,
        DownloadDocumentTool::class,
        TokenStatusTool::class,
    ]);
});

it('marks every MCP tool as read-only', function () {
    foreach (exactMcpToolClasses() as $toolClass) {
        $attributes = (new ReflectionClass($toolClass))->getAttributes(IsReadOnly::class);

        expect($attributes)->not->toBeEmpty();
    }
});

it('exposes the expected MCP tool names', function () {
    $names = array_map(
        fn (string $toolClass): string => app($toolClass)->name(),
        exactMcpToolClasses(),
    );

    expect($names)->toBe([
        'exact_connections_list',
        'exact_connection_get',
        'exact_local_records_list',
        'exact_local_record_get',
        'exact_api_read',
        'exact_document_download',
        'exact_token_status',
    ]);
});
