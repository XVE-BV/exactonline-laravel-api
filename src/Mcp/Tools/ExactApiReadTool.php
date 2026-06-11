<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use XVE\ExactonlineLaravelApi\Mcp\Support\Anonymizer;
use XVE\ExactonlineLaravelApi\Mcp\Support\ConnectionResolver;
use XVE\ExactonlineLaravelApi\Mcp\Support\ExactReadActionRegistry;
use XVE\ExactonlineLaravelApi\Mcp\Support\SecretScrubber;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

/**
 * Dispatcher tool over all 47 Get* API actions.
 *
 * Security: only get_* / download_* actions are allowed (enforced by the registry,
 * not just the #[IsReadOnly] hint which is informational only for the client).
 * The `top` option is clamped to mcp.limits.max_limit to prevent runaway reads.
 */
#[IsReadOnly]
class ExactApiReadTool extends Tool
{
    use HandlesMcpToolErrors;

    protected string $name = 'exact_api_read';

    protected string $description = <<<'DESC'
        Dispatch a read-only call to any Exact Online API entity. Use entity_list to discover
        all available entity names. Supports OData filter, select, expand, orderby, top, and skip.
        The top option is capped at the configured max_limit (default 200).
        DESC;

    public function schema(JsonSchema $schema): array
    {
        $registry = new ExactReadActionRegistry;
        $entities = $registry->readableEntities();

        return [
            'entity' => $schema->string()
                ->description('Logical action name, e.g. "get_accounts", "get_sales_invoices". Call entity_list first to discover all available names.')
                ->enum($entities),
            'id' => $schema->string()
                ->description('GUID or ID of a specific record. When provided the single-getter variant of the action is called. Omit for collection calls.'),
            'connection_id' => $schema->integer()
                ->description('ID of the connection to use. Defaults to the most recently used active connection.'),
            'connection_name' => $schema->string()
                ->description('Name of the connection. Alternative to connection_id.'),
            'options' => $schema->object()
                ->description('OData query options: filter (string), select (array of strings), expand (array of strings), orderby (string), top (int, capped), skip (int).'),
        ];
    }

    public function handle(Request $request): Response
    {
        return $this->safely(function () use ($request): Response {
            $entity = $request->get('entity');

            if (empty($entity)) {
                return Response::error('entity is required.');
            }

            $registry = new ExactReadActionRegistry;

            try {
                $action = $registry->resolve((string) $entity);
            } catch (\Throwable $e) {
                return Response::error($e->getMessage());
            }

            try {
                $connection = (new ConnectionResolver)->resolve($request->all());
            } catch (\Throwable $e) {
                return Response::error($e->getMessage());
            }

            $options = $this->normaliseOptions($request->get('options'));
            $id = $request->get('id');

            try {
                if (! empty($id) && $registry->isSingleGetter($action)) {
                    return $this->handleSingleRead($registry, (string) $entity, $action, $connection, (string) $id, $options);
                }

                $options = $this->applyCollectionLimit($options);
                $result = $registry->acceptsOptions($action)
                    ? $registry->execute($action, [$connection, $options])
                    : $registry->execute($action, [$connection]);

                $records = $this->scrubRecords($result);

                return Response::json([
                    'entity' => $entity,
                    'count' => is_countable($records) ? count($records) : null,
                    'records' => $records,
                ]);
            } catch (\Throwable $e) {
                return Response::error('Exact API error.');
            }
        });
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function handleSingleRead(ExactReadActionRegistry $registry, string $entity, object $action, ExactConnection $connection, string $id, array $options): Response
    {
        $scrubber = new SecretScrubber;

        if ($this->shouldUseCollectionForSingleRead($options)) {
            $collectionEntity = $registry->collectionEntityForSingle($entity);

            if ($collectionEntity !== null && $this->isGuid($id)) {
                $collectionAction = $registry->resolve($collectionEntity);
                $singleOptions = $this->optionsForSingleCollectionRead($options, $id);
                $result = $registry->acceptsOptions($collectionAction)
                    ? $registry->execute($collectionAction, [$connection, $singleOptions])
                    : $registry->execute($collectionAction, [$connection]);

                $records = $this->scrubRecords($result);
                $record = is_array($records) ? ($records[0] ?? null) : null;

                return Response::json(['record' => $this->normaliseRecord($record)]);
            }

            if (! empty($options['select']) || ! empty($options['expand'])) {
                $note = 'select/expand could not be applied because this entity cannot be safely filtered by GUID; returned the plain single-record result.';
            }
        }

        $result = $registry->acceptsOptions($action)
            ? $registry->execute($action, [$connection, $id, $options])
            : $registry->execute($action, [$connection, $id]);

        $data = $result !== null
            ? $this->anonymizeIfEnabled($scrubber->scrubKnownFields($result))
            : null;
        $response = ['record' => $this->normaliseRecord($data)];

        if (isset($note)) {
            $response['_note'] = $note;
        }

        return Response::json($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function normaliseOptions(mixed $options): array
    {
        return is_array($options) ? $options : [];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function applyCollectionLimit(array $options): array
    {
        $maxLimit = max(1, (int) config('exactonline-laravel-api.mcp.limits.max_limit', 200));

        if (isset($options['top'])) {
            $options['top'] = max(1, min((int) $options['top'], $maxLimit));

            return $options;
        }

        $defaultLimit = max(1, (int) config('exactonline-laravel-api.mcp.limits.default_limit', 50));
        $options['top'] = min($defaultLimit, $maxLimit);

        return $options;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function shouldUseCollectionForSingleRead(array $options): bool
    {
        return ! empty($options['select']) || ! empty($options['expand']);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function optionsForSingleCollectionRead(array $options, string $id): array
    {
        $filter = "ID eq guid'{$id}'";

        if (! empty($options['filter'])) {
            $filter = "({$options['filter']}) and {$filter}";
        }

        $options['filter'] = $filter;
        $options['top'] = 1;

        return $options;
    }

    private function isGuid(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }

    private function normaliseRecord(mixed $record): mixed
    {
        return is_array($record) && $record === [] ? null : $record;
    }

    private function scrubRecords(mixed $result): mixed
    {
        $scrubber = new SecretScrubber;

        if ($result instanceof Collection) {
            return $result->map(function ($item) use ($scrubber) {
                if (! is_array($item)) {
                    return $item;
                }

                return $this->anonymizeIfEnabled($scrubber->scrubKnownFields($item));
            })->all();
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function anonymizeIfEnabled(array $data): array
    {
        if (! config('exactonline-laravel-api.mcp.anonymize', true)) {
            return $data;
        }

        return (new Anonymizer)->anonymize($data);
    }
}
