<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use XVE\ExactonlineLaravelApi\Mcp\Support\SecretScrubber;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

#[IsReadOnly]
class ListConnectionsTool extends Tool
{
    use HandlesMcpToolErrors;

    protected string $name = 'exact_connections_list';

    protected string $description = 'List Exact Online connections stored in the local database. Returns connection metadata (name, division, is_active, last_used_at, token health) with all OAuth credentials removed.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'active_only' => $schema->boolean()
                ->description('When true, return only connections where is_active=true. Default: false (return all).')
                ->default(false),
            'division' => $schema->string()
                ->description('Filter by division code (e.g. "123456"). Optional.'),
            'limit' => $schema->integer()
                ->description('Maximum number of results. Default 50, max 200.')
                ->default(50),
        ];
    }

    public function handle(Request $request): Response
    {
        return $this->safely(function () use ($request): Response {
            $maxLimit = (int) config('exactonline-laravel-api.mcp.limits.max_limit', 200);
            $limit = min((int) ($request->get('limit') ?? 50), $maxLimit);

            $query = ExactConnection::query()->orderByDesc('last_used_at')->limit($limit);

            if ($request->get('active_only')) {
                $query->where('is_active', true);
            }

            if ($division = $request->get('division')) {
                $query->where('division', $division);
            }

            $scrubber = new SecretScrubber;

            $connections = $query->get()->map(function (ExactConnection $conn) use ($scrubber): array {
                $data = $scrubber->scrubFull($conn->toArray());

                // Append a computed token-health summary (no raw token values).
                $data['_token_health'] = [
                    'access_token_set' => ! empty($conn->getAttributes()['access_token']),
                    'refresh_token_set' => ! empty($conn->getAttributes()['refresh_token']),
                    'access_token_needs_refresh' => $conn->tokenNeedsRefresh(),
                    'refresh_token_expiring_soon' => $conn->refreshTokenExpiringSoon(),
                ];

                return $data;
            });

            return Response::json([
                'count' => $connections->count(),
                'connections' => $connections->all(),
            ]);
        });

    }
}
