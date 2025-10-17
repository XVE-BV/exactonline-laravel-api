<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

class SalesInvoiceDeleted extends BaseWebhookEvent
{
    /**
     * Get the event name for logging/tagging
     */
    public function getEventName(): string
    {
        return 'sales_invoice.deleted';
    }

    /**
     * Get the entity type for this event
     */
    public function getEntityType(): string
    {
        return 'SalesInvoice';
    }

    /**
     * Get the action type for this event
     */
    public function getActionType(): string
    {
        return 'Deleted';
    }
}
