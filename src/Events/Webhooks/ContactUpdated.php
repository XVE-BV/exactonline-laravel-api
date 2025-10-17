<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

class ContactUpdated extends BaseWebhookEvent
{
    /**
     * Get the event name for logging/tagging
     */
    public function getEventName(): string
    {
        return 'contact.updated';
    }

    /**
     * Get the entity type for this event
     */
    public function getEntityType(): string
    {
        return 'Contact';
    }

    /**
     * Get the action type for this event
     */
    public function getActionType(): string
    {
        return 'Updated';
    }
}
