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
class GetLocalRecordTool extends Tool
{
    use HandlesMcpToolErrors;

    protected string $name = 'exact_local_record_get';

    protected string $description = 'Get a single local database record by record_type and id. Credentials are removed from webhook output.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'record_type' => $schema->string()
                ->description('The type of local record: "mappings", "rate_limits", "webhooks", or "divisions".')
                ->enum(['mappings', 'rate_limits', 'webhooks', 'divisions']),
            'id' => $schema->integer()
                ->description('The primary key of the record to retrieve.'),
        ];
    }

    public function handle(Request $request): Response
    {
        return $this->safely(function () use ($request): Response {
            $type = $request->get('record_type');
            $id = $request->get('id');

            if (empty($type)) {
                return Response::error('record_type is required.');
            }

            if (empty($id)) {
                return Response::error('id is required.');
            }

            $registry = new LocalModelRegistry;

            try {
                $modelClass = $registry->modelClass($type);
            } catch (\Throwable $e) {
                return Response::error($e->getMessage());
            }

            $record = $modelClass::find($id);

            if ($record === null) {
                return Response::error("No {$type} record found with id={$id}.");
            }

            $scrubber = new SecretScrubber;

            return Response::json($scrubber->scrubFull($record->toArray()));
        });

    }
}
