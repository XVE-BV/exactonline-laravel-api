<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

class FinancialTransactionCreated extends BaseWebhookEvent
{
    /**
     * Get the event name for logging/tagging
     */
    public function getEventName(): string
    {
        return 'financial_transaction.created';
    }

    /**
     * Get the entity type for this event
     */
    public function getEntityType(): string
    {
        return 'FinancialTransaction';
    }

    /**
     * Get the action type for this event
     */
    public function getActionType(): string
    {
        return 'Created';
    }
}
