<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\WebhookSubscription;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreateWebhookSubscriptionAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Create a new webhook subscription in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array{
     *     CallbackURL: string,
     *     Topic: string,
     *     Description?: string|null
     * }  $data  Webhook subscription data following Exact Online's schema
     * @return array<string, mixed> The created webhook subscription data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateCreatePayload('WebhookSubscription', $data);
        $this->validateSubscriptionData($data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $webhookSubscription = new WebhookSubscription($picqerConnection);

            foreach ($data as $key => $value) {
                $webhookSubscription->{$key} = $value;
            }

            $webhookSubscription->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created webhook subscription in Exact Online', [
                'connection_id' => $connection->id,
                'subscription_id' => $webhookSubscription->ID,
                'callback_url' => $webhookSubscription->CallbackURL,
                'topic' => $webhookSubscription->Topic,
            ]);

            return $webhookSubscription->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create webhook subscription in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new ConnectionException(
                'Failed to create webhook subscription: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Validate required webhook subscription data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateSubscriptionData(array $data): void
    {
        if (empty($data['CallbackURL'])) {
            throw ConnectionException::invalidConfiguration(
                'Webhook subscription CallbackURL is required'
            );
        }

        if (empty($data['Topic'])) {
            throw ConnectionException::invalidConfiguration(
                'Webhook subscription Topic is required'
            );
        }
    }
}
