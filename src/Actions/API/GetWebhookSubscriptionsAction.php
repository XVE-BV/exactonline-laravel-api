<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\WebhookSubscription;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetWebhookSubscriptionsAction
{
    use HandlesExactConnection;

    /**
     * Retrieve webhook subscriptions from Exact Online.
     *
     * @param  array<string, mixed>  $options  OData query options
     * @return Collection<int, array<string, mixed>>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $options = []): Collection
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $webhookSubscription = new WebhookSubscription($picqerConnection);

            $this->applyQueryOptions($webhookSubscription, $options);

            $subscriptions = $webhookSubscription->get();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Retrieved webhook subscriptions from Exact Online', [
                'connection_id' => $connection->id,
                'count' => count($subscriptions),
            ]);

            return collect($subscriptions)->map(fn ($s) => $s->attributes());

        } catch (\Exception $e) {
            Log::error('Failed to retrieve webhook subscriptions from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve webhook subscriptions: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function applyQueryOptions(WebhookSubscription $entity, array $options): void
    {
        if (! empty($options['filter'])) {
            $entity->filter($options['filter']);
        }
        if (! empty($options['select'])) {
            $entity->select($options['select']);
        }
        if (! empty($options['top'])) {
            $entity->top($options['top']);
        }
    }
}
