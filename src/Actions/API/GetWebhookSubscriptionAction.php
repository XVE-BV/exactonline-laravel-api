<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\WebhookSubscription;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetWebhookSubscriptionAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single webhook subscription from Exact Online.
     *
     * @param  string  $subscriptionId  The Exact Online webhook subscription ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $subscriptionId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $webhookSubscription = new WebhookSubscription($picqerConnection);

            $result = $webhookSubscription->find($subscriptionId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved webhook subscription from Exact Online', [
                'connection_id' => $connection->id,
                'subscription_id' => $subscriptionId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve webhook subscription from Exact Online', [
                'connection_id' => $connection->id,
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve webhook subscription: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
