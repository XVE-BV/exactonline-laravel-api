<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Events\Webhooks;

use Illuminate\Contracts\Queue\ShouldQueue;

class SalesOrderUpdated extends BaseWebhookEvent implements ShouldQueue
{
    public function getEventName(): string
    {
        return 'sales_order.updated';
    }

    public function getEntityType(): string
    {
        return 'SalesOrder';
    }

    public function getActionType(): string
    {
        return 'Updated';
    }
}
