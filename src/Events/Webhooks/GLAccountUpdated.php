<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

class GLAccountUpdated extends BaseWebhookEvent
{
    /**
     * Get the event name for logging/tagging
     */
    public function getEventName(): string
    {
        return 'gl_account.updated';
    }

    /**
     * Get the entity type for this event
     */
    public function getEntityType(): string
    {
        return 'GLAccount';
    }

    /**
     * Get the action type for this event
     */
    public function getActionType(): string
    {
        return 'Updated';
    }
}
