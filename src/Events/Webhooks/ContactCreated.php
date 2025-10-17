<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

class ContactCreated extends BaseWebhookEvent
{
    /**
     * Get the event name for logging/tagging
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'contact.created';
    }

    /**
     * Get the entity type for this event
     *
     * @return string
     */
    public function getEntityType(): string
    {
        return 'Contact';
    }

    /**
     * Get the action type for this event
     *
     * @return string
     */
    public function getActionType(): string
    {
        return 'Created';
    }
}
