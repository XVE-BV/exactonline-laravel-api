<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Events\Webhooks;

use Illuminate\Contracts\Queue\ShouldQueue;

class SubscriptionCreated extends BaseWebhookEvent implements ShouldQueue
{
    public function getEventName(): string
    {
        return 'subscription.created';
    }

    public function getEntityType(): string
    {
        return 'Subscription';
    }

    public function getActionType(): string
    {
        return 'Created';
    }
}
