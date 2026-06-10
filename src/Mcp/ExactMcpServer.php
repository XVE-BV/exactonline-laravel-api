<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Mcp;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Tool;
use XVE\ExactonlineLaravelApi\Mcp\Tools\DownloadDocumentTool;
use XVE\ExactonlineLaravelApi\Mcp\Tools\ExactApiReadTool;
use XVE\ExactonlineLaravelApi\Mcp\Tools\GetConnectionTool;
use XVE\ExactonlineLaravelApi\Mcp\Tools\GetLocalRecordTool;
use XVE\ExactonlineLaravelApi\Mcp\Tools\ListConnectionsTool;
use XVE\ExactonlineLaravelApi\Mcp\Tools\ListLocalRecordsTool;
use XVE\ExactonlineLaravelApi\Mcp\Tools\TokenStatusTool;

class ExactMcpServer extends Server
{
    protected string $name = 'Exact Online MCP';

    protected string $version = '1.0.0';

    protected string $instructions = 'Read-only MCP server for the Exact Online integration. Inspect connections, mappings, rate limits, divisions, webhooks, and live Exact Online API data. No OAuth credentials or secrets are ever returned.';

    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        ListConnectionsTool::class,
        GetConnectionTool::class,
        ListLocalRecordsTool::class,
        GetLocalRecordTool::class,
        ExactApiReadTool::class,
        DownloadDocumentTool::class,
        TokenStatusTool::class,
    ];
}
