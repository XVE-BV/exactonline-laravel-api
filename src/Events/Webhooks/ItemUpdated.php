<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

class ItemUpdated extends BaseWebhookEvent
{
    /**
     * Get the event name for logging/tagging
     */
    public function getEventName(): string
    {
        return 'item.updated';
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
        return 'Updated';
    }
}
