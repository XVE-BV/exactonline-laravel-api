<?php

declare(strict_types=1);

namespace XVE\Exactonline\Events\Webhooks;

use Illuminate\Contracts\Queue\ShouldQueue;

class StockPositionUpdated extends BaseWebhookEvent implements ShouldQueue
{
    public function getEventName(): string
    {
        return 'stock_position.updated';
    }

    public function getEntityType(): string
    {
        return 'StockPosition';
    }

    public function getActionType(): string
    {
        return 'Updated';
    }
}
