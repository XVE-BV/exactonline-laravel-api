<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\Webhooks;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Models\ExactWebhook;
use Skylence\ExactonlineLaravelApi\Support\Config;

class RegisterWebhookAction
{
    /**
     * Register a webhook subscription with Exact Online
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $topic  The webhook topic (e.g., 'Accounts', 'SalesInvoices')
     * @param  string|null  $callbackUrl  The callback URL (defaults to config)
     * @return ExactWebhook The created webhook model
     *
     * @throws ConnectionException
     */
    public function execute(
        ExactConnection $connection,
        string $topic,
        ?string $callbackUrl = null
    ): ExactWebhook {
        // Validate topic
        $this->validateTopic($topic);

        // Ensure we have a valid access token
        $this->ensureValidToken($connection);

        // Check rate limits
        $this->checkRateLimit($connection);

        // Determine callback URL
        $callbackUrl = $callbackUrl ?? $this->getDefaultCallbackUrl();

        // Generate webhook secret
        $webhookSecret = Str::random(32);

        try {
            // Register webhook with Exact Online API
            $webhookData = $this->registerWithExactOnline(
                $connection,
                $topic,
                $callbackUrl,
                $webhookSecret
            );

            // Create or update local webhook record
            $webhook = ExactWebhook::updateOrCreate(
                [
                    'connection_id' => $connection->id,
                    'topic' => $topic,
                ],
                [
                    'webhook_id' => $webhookData['ID'] ?? Str::uuid()->toString(),
                    'callback_url' => $callbackUrl,
                    'webhook_secret' => encrypt($webhookSecret),
                    'is_active' => true,
                    'metadata' => $webhookData,
                ]
            );

            Log::info('Webhook registered with Exact Online', [
                'connection_id' => $connection->id,
                'webhook_id' => $webhook->webhook_id,
                'topic' => $topic,
                'callback_url' => $callbackUrl,
            ]);

            return $webhook;

        } catch (\Exception $e) {
            Log::error('Failed to register webhook with Exact Online', [
                'connection_id' => $connection->id,
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to register webhook: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Validate webhook topic
     *
     * @param  string  $topic
     *
     * @throws ConnectionException
     */
    protected function validateTopic(string $topic): void
    {
        $allowedTopics = config('exactonline-laravel-api.webhooks.topics', []);

        if (! empty($allowedTopics) && ! in_array($topic, $allowedTopics, true)) {
            throw ConnectionException::invalidConfiguration(
                "Invalid webhook topic '{$topic}'. Allowed topics: " . implode(', ', $allowedTopics)
            );
        }

        // List of known Exact Online webhook topics
        $knownTopics = [
            'Accounts',
            'BankAccounts',
            'Contacts',
            'CostCenters',
            'CostUnits',
            'Documents',
            'DocumentAttachments',
            'FinancialTransactions',
            'GLAccounts',
            'GoodsDeliveries',
            'Items',
            'Projects',
            'PurchaseInvoices',
            'Quotations',
            'SalesInvoices',
            'SalesOrders',
            'StockPositions',
            'TimeTransactions',
            'Subscriptions',
            'SubscriptionLines',
            // Add more as needed based on Exact Online documentation
        ];

        if (! in_array($topic, $knownTopics, true)) {
            Log::warning('Registering webhook for unknown topic', [
                'topic' => $topic,
                'known_topics' => $knownTopics,
            ]);
        }
    }

    /**
     * Get default callback URL from configuration
     *
     * @return string
     */
    protected function getDefaultCallbackUrl(): string
    {
        $path = config('exactonline-laravel-api.webhooks.path', '/exact/webhooks');
        
        return url($path);
    }

    /**
     * Register webhook with Exact Online API
     *
     * @param  ExactConnection  $connection
     * @param  string  $topic
     * @param  string  $callbackUrl
     * @param  string  $webhookSecret
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    protected function registerWithExactOnline(
        ExactConnection $connection,
        string $topic,
        string $callbackUrl,
        string $webhookSecret
    ): array {
        // Get the picqer connection
        $picqerConnection = $connection->getPicqerConnection();

        // Prepare webhook subscription data
        $subscriptionData = [
            'Topic' => $topic,
            'CallbackURL' => $callbackUrl,
            'ClientSecret' => $webhookSecret,
            'Division' => $connection->division ?? $picqerConnection->getDivision(),
        ];

        // Make API call to register webhook
        // Note: The exact endpoint and method may vary based on Exact Online's webhook API
        // This is a generic implementation that may need adjustment
        $url = $picqerConnection->getApiUrl() . '/webhooks/WebhookSubscriptions';

        $response = Http::withToken(decrypt($connection->access_token))
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post($url, $subscriptionData);

        if (! $response->successful()) {
            throw new \Exception(
                'Webhook registration failed: ' . $response->body(),
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Ensure the connection has a valid access token
     */
    protected function ensureValidToken(ExactConnection $connection): void
    {
        if ($this->tokenNeedsRefresh($connection)) {
            $refreshAction = Config::getAction(
                'refresh_access_token',
                RefreshAccessTokenAction::class
            );
            $refreshAction->execute($connection);

            // Refresh the connection to get updated tokens
            $connection->refresh();
        }
    }

    /**
     * Check if token needs refresh (proactive at 9 minutes)
     */
    protected function tokenNeedsRefresh(ExactConnection $connection): bool
    {
        if (empty($connection->token_expires_at)) {
            return true;
        }

        // Refresh proactively at 9 minutes (540 seconds before expiry)
        return $connection->token_expires_at < (now()->timestamp + 540);
    }

    /**
     * Check rate limits before making the API request
     */
    protected function checkRateLimit(ExactConnection $connection): void
    {
        $checkRateLimitAction = Config::getAction(
            'check_rate_limit',
            CheckRateLimitAction::class
        );
        $checkRateLimitAction->execute($connection);
    }
}
