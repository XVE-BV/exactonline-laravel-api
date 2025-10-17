<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

class ItemCreated extends BaseWebhookEvent
{
    /**
     * Get the event name for logging/tagging
     */
    public function getEventName(): string
    {
        return 'item.created';
    }

    /**
     * Get the entity type for this event
     */
    public function getEntityType(): string
    {
        return 'Item';
    }

    /**
     * Get the action type for this event
     */
    public function getActionType(): string
    {
        return 'Created';
    }
}
