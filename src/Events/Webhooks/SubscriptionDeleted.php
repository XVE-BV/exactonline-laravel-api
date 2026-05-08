<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

use Illuminate\Contracts\Queue\ShouldQueue;

class SubscriptionDeleted extends BaseWebhookEvent implements ShouldQueue
{
    public function getEventName(): string
    {
        return 'subscription.deleted';
    }

    public function getEntityType(): string
    {
        return 'Subscription';
    }

    public function getActionType(): string
    {
        return 'Deleted';
    }
}
