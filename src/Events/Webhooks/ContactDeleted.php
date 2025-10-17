<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

class ContactDeleted extends BaseWebhookEvent
{
    /**
     * Get the event name for logging/tagging
     */
    public function getEventName(): string
    {
        return 'contact.deleted';
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
        return 'Deleted';
    }
}
