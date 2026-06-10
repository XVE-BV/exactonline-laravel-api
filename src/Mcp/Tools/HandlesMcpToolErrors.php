<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Mcp\Tools;

use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Response;
use Throwable;

trait HandlesMcpToolErrors
{
    /**
     * @param  callable(): Response  $callback
     */
    private function safely(callable $callback): Response
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            Log::error('Unhandled MCP tool failure', [
                'tool' => static::class,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return Response::error('An internal error occurred while handling the MCP request.');
        }
    }
}
