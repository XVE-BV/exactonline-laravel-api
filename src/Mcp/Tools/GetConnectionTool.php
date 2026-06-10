<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use XVE\ExactonlineLaravelApi\Mcp\Support\ConnectionResolver;
use XVE\ExactonlineLaravelApi\Mcp\Support\SecretScrubber;

#[IsReadOnly]
class GetConnectionTool extends Tool
{
    use HandlesMcpToolErrors;

    protected string $name = 'exact_connection_get';

    protected string $description = 'Get details of a single Exact Online connection including token health, rate-limit state, and associated divisions. All OAuth credentials are removed from the output.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'connection_id' => $schema->integer()
                ->description('ID of the connection to retrieve. If omitted, returns the most recently used active connection.'),
            'connection_name' => $schema->string()
                ->description('Name of the connection. Used when connection_id is not provided.'),
            'include_webhooks' => $schema->boolean()
                ->description('Include associated webhook subscriptions in the response. Default: false.')
                ->default(false),
            'include_divisions' => $schema->boolean()
                ->description('Include synced divisions in the response. Default: false.')
                ->default(false),
        ];
    }

    public function handle(Request $request): Response
    {
        return $this->safely(function () use ($request): Response {
            try {
                $connection = (new ConnectionResolver)->resolve($request->all());
            } catch (\Throwable $e) {
                return Response::error($e->getMessage());
            }

            $scrubber = new SecretScrubber;
            $data = $scrubber->scrubFull($connection->toArray());

            // Token health computed from model fields without decrypting anything.
            $data['_token_health'] = [
                'access_token_set' => ! empty($connection->getAttributes()['access_token']),
                'refresh_token_set' => ! empty($connection->getAttributes()['refresh_token']),
                'access_token_needs_refresh' => $connection->tokenNeedsRefresh(),
                'refresh_token_expiring_soon' => $connection->refreshTokenExpiringSoon(),
                'token_expires_at' => $connection->token_expires_at,
                'refresh_token_expires_at' => $connection->refresh_token_expires_at,
            ];

            // Rate-limit state.
            $rateLimit = $connection->rateLimit;
            $data['_rate_limit'] = $rateLimit ? $scrubber->scrubFull($rateLimit->toArray()) : null;

            if ($request->get('include_webhooks')) {
                $data['webhooks'] = $connection->webhooks()
                    ->get()
                    ->map(fn ($w) => $scrubber->scrubFull($w->toArray()))
                    ->all();
            }

            if ($request->get('include_divisions')) {
                $data['divisions'] = $connection->divisions()
                    ->orderByDesc('is_main')
                    ->get()
                    ->map(fn ($d) => $d->toArray())
                    ->all();
            }

            return Response::json($data);
        });

    }
}
