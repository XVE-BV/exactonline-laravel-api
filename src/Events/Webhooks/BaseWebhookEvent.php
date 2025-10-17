<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Skylence\ExactonlineLaravelApi\Models\ExactWebhook;

abstract class BaseWebhookEvent implements ShouldQueue
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
     * Create a new webhook event instance
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
            $this->getEventName(),
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
    abstract public function getEventName(): string;

    /**
     * Get the entity type for this event
     */
    abstract public function getEntityType(): string;

    /**
     * Get the action type for this event (Created, Updated, Deleted)
     */
    abstract public function getActionType(): string;
}
