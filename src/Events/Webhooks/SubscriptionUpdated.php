<?php

declare(strict_types=1);

namespace XVE\Exactonline\Events\Webhooks;

use Illuminate\Contracts\Queue\ShouldQueue;

class SubscriptionUpdated extends BaseWebhookEvent implements ShouldQueue
{
    public function getEventName(): string
    {
        return 'subscription.updated';
    }

    public function getEntityType(): string
    {
        return 'Subscription';
    }

    public function getActionType(): string
    {
        return 'Updated';
    }
}
