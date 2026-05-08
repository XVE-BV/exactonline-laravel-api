<?php

declare(strict_types=1);

namespace XVE\Exactonline\Events\Webhooks;

use Illuminate\Contracts\Queue\ShouldQueue;

class PurchaseInvoiceUpdated extends BaseWebhookEvent implements ShouldQueue
{
    public function getEventName(): string
    {
        return 'purchase_invoice.updated';
    }

    public function getEntityType(): string
    {
        return 'PurchaseInvoice';
    }

    public function getActionType(): string
    {
        return 'Updated';
    }
}
