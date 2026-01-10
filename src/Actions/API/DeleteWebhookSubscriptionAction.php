<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\WebhookSubscription;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class DeleteWebhookSubscriptionAction
{
    use HandlesExactConnection;

    /**
     * Delete a webhook subscription from Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $subscriptionId  The Exact Online webhook subscription ID (GUID)
     * @return bool True if the subscription was successfully deleted
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $subscriptionId): bool
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $webhookSubscription = new WebhookSubscription($picqerConnection);
            $webhookSubscription->ID = $subscriptionId;

            $webhookSubscription->delete();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Deleted webhook subscription from Exact Online', [
                'connection_id' => $connection->id,
                'subscription_id' => $subscriptionId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete webhook subscription from Exact Online', [
                'connection_id' => $connection->id,
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to delete webhook subscription: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
