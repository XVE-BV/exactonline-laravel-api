<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Exceptions\WebhookValidationException;

class ProcessWebhookPayloadAction
{
    /**
     * Process webhook payload and extract event data
     *
     * @param  Request  $request  The incoming webhook request
     * @return array{
     *     topic: string,
     *     action: string,
     *     entity: string,
     *     entity_id: string|null,
     *     division: string|null,
     *     timestamp: int,
     *     data: array<string, mixed>,
     *     metadata: array<string, mixed>
     * }
     *
     * @throws WebhookValidationException
     */
    public function execute(Request $request): array
    {
        $payload = $request->json()->all();

        if (empty($payload)) {
            throw WebhookValidationException::invalidPayload('Empty webhook payload received');
        }

        // Extract standard fields
        $topic = $this->extractTopic($payload);
        $action = $this->extractAction($payload);
        $entity = $this->extractEntity($payload);
        $entityId = $this->extractEntityId($payload);
        $division = $this->extractDivision($payload);
        $timestamp = $this->extractTimestamp($payload);

        // Extract entity data
        $data = $this->extractEntityData($payload);

        // Extract any additional metadata
        $metadata = $this->extractMetadata($payload);

        $processed = [
            'topic' => $topic,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'division' => $division,
            'timestamp' => $timestamp,
            'data' => $data,
            'metadata' => $metadata,
        ];

        Log::info('Webhook payload processed', [
            'topic' => $topic,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
        ]);

        return $processed;
    }

    /**
     * Extract webhook topic
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws WebhookValidationException
     */
    protected function extractTopic(array $payload): string
    {
        $topic = $payload['Topic'] ?? $payload['topic'] ?? null;

        if (empty($topic)) {
            throw WebhookValidationException::invalidPayload('No topic found in webhook payload');
        }

        return $topic;
    }

    /**
     * Extract webhook action (Created, Updated, Deleted, etc.)
     *
     * @param  array<string, mixed>  $payload
     */
    protected function extractAction(array $payload): string
    {
        // Try different field names
        $action = $payload['Action'] ??
                 $payload['action'] ??
                 $payload['EventType'] ??
                 $payload['event_type'] ??
                 'Unknown';

        // If action is embedded in topic (e.g., "AccountsCreated")
        if ($action === 'Unknown' && isset($payload['Topic'])) {
            $topic = $payload['Topic'];

            if (str_ends_with($topic, 'Created')) {
                return 'Created';
            } elseif (str_ends_with($topic, 'Updated')) {
                return 'Updated';
            } elseif (str_ends_with($topic, 'Deleted')) {
                return 'Deleted';
            }
        }

        return $action;
    }

    /**
     * Extract entity type from webhook
     *
     * @param  array<string, mixed>  $payload
     */
    protected function extractEntity(array $payload): string
    {
        // Try to extract from explicit field
        $entity = $payload['Entity'] ?? $payload['entity'] ?? null;

        if (! empty($entity)) {
            return $entity;
        }

        // Extract from topic (remove action suffix)
        $topic = $payload['Topic'] ?? '';

        $entity = preg_replace('/(Created|Updated|Deleted)$/', '', $topic);

        return $entity ?: 'Unknown';
    }

    /**
     * Extract entity ID
     *
     * @param  array<string, mixed>  $payload
     */
    protected function extractEntityId(array $payload): ?string
    {
        // Try common field names
        $id = $payload['ID'] ??
              $payload['Id'] ??
              $payload['id'] ??
              $payload['EntityID'] ??
              $payload['entity_id'] ??
              $payload['Key'] ??
              $payload['key'] ??
              null;

        // If ID is in the data object
        if ($id === null && isset($payload['Data'])) {
            $id = $payload['Data']['ID'] ??
                  $payload['Data']['Id'] ??
                  $payload['Data']['id'] ??
                  null;
        }

        return $id !== null ? (string) $id : null;
    }

    /**
     * Extract division (administration)
     *
     * @param  array<string, mixed>  $payload
     */
    protected function extractDivision(array $payload): ?string
    {
        $division = $payload['Division'] ??
                   $payload['division'] ??
                   $payload['Administration'] ??
                   $payload['administration'] ??
                   null;

        return $division !== null ? (string) $division : null;
    }

    /**
     * Extract timestamp
     *
     * @param  array<string, mixed>  $payload
     */
    protected function extractTimestamp(array $payload): int
    {
        $timestamp = $payload['Timestamp'] ??
                    $payload['timestamp'] ??
                    $payload['CreatedAt'] ??
                    $payload['created_at'] ??
                    $payload['EventTime'] ??
                    $payload['event_time'] ??
                    null;

        if ($timestamp === null) {
            return now()->timestamp;
        }

        // If it's already a Unix timestamp
        if (is_numeric($timestamp)) {
            // Check if it's in milliseconds
            if ($timestamp > 9999999999) {
                return (int) ($timestamp / 1000);
            }

            return (int) $timestamp;
        }

        // Parse string timestamp
        $parsed = strtotime($timestamp);

        return $parsed !== false ? $parsed : now()->timestamp;
    }

    /**
     * Extract entity data
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function extractEntityData(array $payload): array
    {
        // Check for explicit data field
        if (isset($payload['Data']) && is_array($payload['Data'])) {
            return $payload['Data'];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        // Check for Content field (some webhooks use this)
        if (isset($payload['Content']) && is_array($payload['Content'])) {
            return $payload['Content'];
        }

        // Remove metadata fields and return the rest as data
        $metadataFields = [
            'Topic', 'topic',
            'Action', 'action',
            'EventType', 'event_type',
            'Timestamp', 'timestamp',
            'Division', 'division',
            'Administration', 'administration',
            'Signature', 'signature',
            '_metadata',
        ];

        $data = array_diff_key($payload, array_flip($metadataFields));

        return $data;
    }

    /**
     * Extract additional metadata
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function extractMetadata(array $payload): array
    {
        $metadata = [];

        // Check for explicit metadata field
        if (isset($payload['_metadata']) && is_array($payload['_metadata'])) {
            $metadata = $payload['_metadata'];
        }

        // Add webhook-specific metadata
        $metadata['raw_topic'] = $payload['Topic'] ?? null;
        $metadata['received_at'] = now()->toIso8601String();

        // Add user/account information if present
        if (isset($payload['User'])) {
            $metadata['user'] = $payload['User'];
        }

        if (isset($payload['AccountName'])) {
            $metadata['account_name'] = $payload['AccountName'];
        }

        // Add any custom fields that don't fit standard structure
        $standardFields = [
            'Topic', 'topic',
            'Action', 'action',
            'ID', 'Id', 'id',
            'Data', 'data',
            'Content',
            'Timestamp', 'timestamp',
            'Division', 'division',
        ];

        foreach ($payload as $key => $value) {
            if (! in_array($key, $standardFields) && ! isset($metadata[$key])) {
                $metadata[$key] = $value;
            }
        }

        return $metadata;
    }

    /**
     * Validate required fields are present
     *
     * @param  array<string, mixed>  $processed
     *
     * @throws WebhookValidationException
     */
    public function validate(array $processed): void
    {
        if (empty($processed['topic'])) {
            throw WebhookValidationException::invalidPayload('Topic is required');
        }

        if (empty($processed['action'])) {
            throw WebhookValidationException::invalidPayload('Action is required');
        }

        if (empty($processed['entity'])) {
            throw WebhookValidationException::invalidPayload('Entity type is required');
        }
    }
}
