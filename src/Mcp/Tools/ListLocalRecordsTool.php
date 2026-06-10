<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use XVE\ExactonlineLaravelApi\Mcp\Support\LocalModelRegistry;
use XVE\ExactonlineLaravelApi\Mcp\Support\SecretScrubber;

#[IsReadOnly]
class ListLocalRecordsTool extends Tool
{
    use HandlesMcpToolErrors;

    protected string $name = 'exact_local_records_list';

    protected string $description = 'List local database records for a given Exact Online record type (mappings, rate_limits, webhooks, or divisions). Credentials are removed from webhook output.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'record_type' => $schema->string()
                ->description('The type of local record to list: "mappings", "rate_limits", "webhooks", or "divisions".')
                ->enum(['mappings', 'rate_limits', 'webhooks', 'divisions']),
            'connection_id' => $schema->integer()
                ->description('Filter results to a specific connection ID. Optional.'),
            'filters' => $schema->object()
                ->description('Additional column filters as key-value pairs (only filterable columns are accepted). Optional.'),
            'limit' => $schema->integer()
                ->description('Maximum number of results. Default 50, max 200.')
                ->default(50),
        ];
    }

    public function handle(Request $request): Response
    {
        return $this->safely(function () use ($request): Response {
            $type = $request->get('record_type');

            if (empty($type)) {
                return Response::error('record_type is required.');
            }

            $registry = new LocalModelRegistry;

            try {
                $modelClass = $registry->modelClass($type);
            } catch (\Throwable $e) {
                return Response::error($e->getMessage());
            }

            $maxLimit = (int) config('exactonline-laravel-api.mcp.limits.max_limit', 200);
            $limit = min((int) ($request->get('limit') ?? 50), $maxLimit);

            $query = $modelClass::query()->limit($limit);

            // Apply connection filter.
            if ($connectionId = $request->get('connection_id')) {
                $query->where('connection_id', $connectionId);
            }

            // Apply additional column filters (only allowed columns).
            $allowedFilters = $registry->filterableColumns($type);
            $extraFilters = $request->get('filters');

            if (is_array($extraFilters)) {
                foreach ($extraFilters as $col => $val) {
                    if (in_array($col, $allowedFilters, true)) {
                        $query->where($col, $val);
                    }
                }
            }

            $scrubber = new SecretScrubber;

            $records = $query->get()->map(function ($record) use ($scrubber): array {
                return $scrubber->scrubFull($record->toArray());
            });

            return Response::json([
                'record_type' => $type,
                'count' => $records->count(),
                'records' => $records->all(),
            ]);
        });

    }
}
