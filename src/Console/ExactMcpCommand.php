<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Laravel\Mcp\Facades\Mcp;

class ExactMcpCommand extends Command
{
    protected $signature = 'exact:mcp';

    protected $description = 'Start the Exact Online MCP server over stdio';

    public function handle(): int
    {
        if (! config('exactonline-laravel-api.mcp.enabled', false)) {
            $this->error('The Exact MCP server is disabled.');
            $this->line('Set EXACT_MCP_ENABLED=true in your .env to enable it.');

            return self::FAILURE;
        }

        if (! class_exists(Mcp::class)) {
            $this->error('The laravel/mcp package is not installed.');
            $this->line('Run: composer require laravel/mcp');

            return self::FAILURE;
        }

        if (! config('exactonline-laravel-api.mcp.stdio.enabled', true)) {
            $this->error('The Exact MCP stdio transport is disabled.');
            $this->line('Set EXACT_MCP_STDIO_ENABLED=true to enable it.');

            return self::FAILURE;
        }

        return Artisan::call('mcp:start', ['handle' => 'exact']);
    }
}
