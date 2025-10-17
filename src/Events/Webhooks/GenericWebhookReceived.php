<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Skylence\ExactonlineLaravelApi\Models\ExactWebhook;

class GenericWebhookReceived implements ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The webhook payload data
     *
     * @var array<string, mixed>
     */
    public array $payload;

    /**
     * The webhook model (if available)
     */
    public ?ExactWebhook $webhook;

    /**
     * The webhook topic
     */
    public string $topic;

    /**
     * The webhook action
     */
    public string $action;

    /**
     * The entity type
     */
    public string $entity;

    /**
     * The entity ID from the webhook
     */
    public ?string $entityId;

    /**
     * The division (administration) ID
     */
    public ?string $division;

    /**
     * The timestamp of the webhook event
     */
    public int $timestamp;

    /**
     * The entity data from the webhook
     *
     * @var array<string, mixed>
     */
    public array $data;

    /**
     * Additional metadata
     *
     * @var array<string, mixed>
     */
    public array $metadata;

    /**
     * Create a new generic webhook event instance
     *
     * @param  array{
     *     topic: string,
     *     action: string,
     *     entity: string,
     *     entity_id: string|null,
     *     division: string|null,
     *     timestamp: int,
     *     data: array<string, mixed>,
     *     metadata: array<string, mixed>
     * }  $payload
     */
    public function __construct(array $payload, ?ExactWebhook $webhook = null)
    {
        $this->payload = $payload;
        $this->webhook = $webhook;
        $this->topic = $payload['topic'] ?? 'unknown';
        $this->action = $payload['action'] ?? 'unknown';
        $this->entity = $payload['entity'] ?? 'unknown';
        $this->entityId = $payload['entity_id'] ?? null;
        $this->division = $payload['division'] ?? null;
        $this->timestamp = $payload['timestamp'] ?? now()->timestamp;
        $this->data = $payload['data'] ?? [];
        $this->metadata = $payload['metadata'] ?? [];
    }

    /**
     * Get the tags that should be assigned to the job
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        $tags = [
            'webhook',
            'exact-online',
            'generic',
            $this->topic,
        ];

        if ($this->entityId !== null) {
            $tags[] = 'entity:'.$this->entityId;
        }

        if ($this->division !== null) {
            $tags[] = 'division:'.$this->division;
        }

        if ($this->webhook !== null) {
            $tags[] = 'webhook:'.$this->webhook->id;
        }

        return $tags;
    }

    /**
     * Get the event name for logging/tagging
     */
    public function getEventName(): string
    {
        return 'webhook.generic';
    }
}
