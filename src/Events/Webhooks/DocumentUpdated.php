<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

class DocumentUpdated extends BaseWebhookEvent
{
    /**
     * Get the event name for logging/tagging
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'document.updated';
    }

    /**
     * Get the entity type for this event
     *
     * @return string
     */
    public function getEntityType(): string
    {
        return 'Document';
    }

    /**
     * Get the action type for this event
     *
     * @return string
     */
    public function getActionType(): string
    {
        return 'Updated';
    }
}
