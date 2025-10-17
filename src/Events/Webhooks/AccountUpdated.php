<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

class AccountUpdated extends BaseWebhookEvent
{
    /**
     * Get the event name for logging/tagging
     */
    public function getEventName(): string
    {
        return 'account.updated';
    }

    /**
     * Get the entity type for this event
     */
    public function getEntityType(): string
    {
        return 'Account';
    }

    /**
     * Get the action type for this event
     */
    public function getActionType(): string
    {
        return 'Updated';
    }
}
