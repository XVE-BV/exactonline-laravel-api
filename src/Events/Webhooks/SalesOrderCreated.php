<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

use Illuminate\Contracts\Queue\ShouldQueue;

class SalesOrderCreated extends BaseWebhookEvent implements ShouldQueue
{
    public function getEventName(): string
    {
        return 'sales_order.created';
    }

    public function getEntityType(): string
    {
        return 'SalesOrder';
    }

    public function getActionType(): string
    {
        return 'Created';
    }
}
