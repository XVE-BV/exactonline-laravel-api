<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

class SalesInvoiceCreated extends BaseWebhookEvent
{
    /**
     * Get the event name for logging/tagging
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'sales_invoice.created';
    }

    /**
     * Get the entity type for this event
     *
     * @return string
     */
    public function getEntityType(): string
    {
        return 'SalesInvoice';
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
