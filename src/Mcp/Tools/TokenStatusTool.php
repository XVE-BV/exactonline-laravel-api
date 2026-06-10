<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use XVE\ExactonlineLaravelApi\Mcp\Support\ConnectionResolver;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

/**
 * Read-only OAuth token / refresh-token health for one or all connections.
 *
 * Computes health directly from stored timestamps. Never calls
 * MonitorRefreshTokenExpiryAction (which dispatches events and mutates metadata).
 */
#[IsReadOnly]
class TokenStatusTool extends Tool
{
    use HandlesMcpToolErrors;

    protected string $name = 'exact_token_status';

    protected string $description = 'Read-only token health status for Exact Online connections. Reports whether access/refresh tokens are valid, expiring, or expired. No tokens are decrypted or returned.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'connection_id' => $schema->integer()
                ->description('ID of a specific connection. Omit to return status for all active connections.'),
            'connection_name' => $schema->string()
                ->description('Name of the connection. Alternative to connection_id for single-connection queries.'),
        ];
    }

    public function handle(Request $request): Response
    {
        return $this->safely(function () use ($request): Response {
            $hasSingleSelector = ! empty($request->get('connection_id'))
                || ! empty($request->get('connection_name'));

            if ($hasSingleSelector) {
                try {
                    $connection = (new ConnectionResolver)->resolve($request->all());
                } catch (\Throwable $e) {
                    return Response::error($e->getMessage());
                }

                return Response::json($this->buildStatus($connection));
            }

            $connections = ExactConnection::where('is_active', true)
                ->orderByDesc('last_used_at')
                ->get();

            return Response::json([
                'count' => $connections->count(),
                'connections' => $connections->map(fn ($c) => $this->buildStatus($c))->all(),
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStatus(ExactConnection $connection): array
    {
        $now = now()->getTimestamp();
        $bufferDays = (int) config('exactonline-laravel-api.oauth.refresh_token_buffer_days', 7);

        $accessExpiresAt = $connection->token_expires_at;
        $refreshExpiresAt = $connection->refresh_token_expires_at;

        $accessSecondsLeft = $accessExpiresAt ? ($accessExpiresAt - $now) : null;
        $refreshSecondsLeft = $refreshExpiresAt ? ($refreshExpiresAt - $now) : null;

        return [
            'connection_id' => $connection->id,
            'connection_name' => $connection->name,
            'is_active' => $connection->is_active,
            'division' => $connection->division,
            'division_id' => $connection->division_id,
            'access_token' => [
                'set' => ! empty($connection->getAttributes()['access_token']),
                'expires_at' => $accessExpiresAt,
                'expires_at_human' => $accessExpiresAt
                    ? Carbon::createFromTimestamp($accessExpiresAt)->toIso8601String()
                    : null,
                'seconds_left' => $accessSecondsLeft,
                'needs_refresh' => $connection->tokenNeedsRefresh(),
                'is_expired' => $accessSecondsLeft !== null && $accessSecondsLeft <= 0,
            ],
            'refresh_token' => [
                'set' => ! empty($connection->getAttributes()['refresh_token']),
                'expires_at' => $refreshExpiresAt,
                'expires_at_human' => $refreshExpiresAt
                    ? Carbon::createFromTimestamp($refreshExpiresAt)->toIso8601String()
                    : null,
                'seconds_left' => $refreshSecondsLeft,
                'expiring_soon' => $connection->refreshTokenExpiringSoon($bufferDays),
                'is_expired' => $refreshSecondsLeft !== null && $refreshSecondsLeft <= 0,
            ],
            'last_token_refresh_at' => $connection->last_token_refresh_at?->toIso8601String(),
            'last_used_at' => $connection->last_used_at?->toIso8601String(),
        ];
    }
}
