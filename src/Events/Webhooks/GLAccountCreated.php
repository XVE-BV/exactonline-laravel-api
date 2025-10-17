<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

class GLAccountCreated extends BaseWebhookEvent
{
    /**
     * Get the event name for logging/tagging
     */
    public function getEventName(): string
    {
        return 'gl_account.created';
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
        return 'Created';
    }
}
