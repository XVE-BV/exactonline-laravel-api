<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\Webhooks;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Models\ExactWebhook;

class DispatchWebhookEventAction
{
    /**
     * Map of webhook topics to event classes
     *
     * @var array<string, string>
     */
    protected array $eventMap = [
        // Account events
        'AccountsCreated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\AccountCreated::class,
        'AccountsUpdated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\AccountUpdated::class,
        'AccountsDeleted' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\AccountDeleted::class,

        // Sales Invoice events
        'SalesInvoicesCreated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\SalesInvoiceCreated::class,
        'SalesInvoicesUpdated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\SalesInvoiceUpdated::class,
        'SalesInvoicesDeleted' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\SalesInvoiceDeleted::class,

        // Contact events
        'ContactsCreated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\ContactCreated::class,
        'ContactsUpdated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\ContactUpdated::class,
        'ContactsDeleted' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\ContactDeleted::class,

        // Document events
        'DocumentsCreated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\DocumentCreated::class,
        'DocumentsUpdated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\DocumentUpdated::class,
        'DocumentsDeleted' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\DocumentDeleted::class,

        // GL Account events
        'GLAccountsCreated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\GLAccountCreated::class,
        'GLAccountsUpdated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\GLAccountUpdated::class,

        // Financial Transaction events
        'FinancialTransactionsCreated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\FinancialTransactionCreated::class,
        'FinancialTransactionsUpdated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\FinancialTransactionUpdated::class,

        // Item events
        'ItemsCreated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\ItemCreated::class,
        'ItemsUpdated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\ItemUpdated::class,
        'ItemsDeleted' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\ItemDeleted::class,

        // Project events
        'ProjectsCreated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\ProjectCreated::class,
        'ProjectsUpdated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\ProjectUpdated::class,
        'ProjectsDeleted' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\ProjectDeleted::class,

        // Purchase Invoice events
        'PurchaseInvoicesCreated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\PurchaseInvoiceCreated::class,
        'PurchaseInvoicesUpdated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\PurchaseInvoiceUpdated::class,

        // Sales Order events
        'SalesOrdersCreated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\SalesOrderCreated::class,
        'SalesOrdersUpdated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\SalesOrderUpdated::class,

        // Stock Position events
        'StockPositionsUpdated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\StockPositionUpdated::class,

        // Subscription events
        'SubscriptionsCreated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\SubscriptionCreated::class,
        'SubscriptionsUpdated' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\SubscriptionUpdated::class,
        'SubscriptionsDeleted' => \Skylence\ExactonlineLaravelApi\Events\Webhooks\SubscriptionDeleted::class,
    ];

    /**
     * Dispatch the appropriate webhook event
     *
     * @param  array{
     *     topic: string,
     *     action: string,
     *     entity: string,
     *     entity_id: string|null,
     *     division: string|null,
     *     timestamp: int,
     *     data: array<string, mixed>,
     *     metadata: array<string, mixed>
     * }  $processedPayload  Processed webhook payload
     * @param  ExactWebhook|null  $webhook  The webhook model (optional)
     * @param  bool  $shouldQueue  Whether to queue the event
     * @return array{
     *     dispatched: bool,
     *     event_class: string|null,
     *     queued: bool,
     *     error: string|null
     * }
     */
    public function execute(
        array $processedPayload,
        ?ExactWebhook $webhook = null,
        ?bool $shouldQueue = null
    ): array {
        $result = [
            'dispatched' => false,
            'event_class' => null,
            'queued' => false,
            'error' => null,
        ];

        try {
            // Determine event class
            $eventClass = $this->determineEventClass($processedPayload);

            if ($eventClass === null) {
                // Dispatch generic webhook event as fallback
                $eventClass = \Skylence\ExactonlineLaravelApi\Events\Webhooks\GenericWebhookReceived::class;
            }

            $result['event_class'] = $eventClass;

            // Determine if event should be queued
            if ($shouldQueue === null) {
                $shouldQueue = $this->shouldQueueEvent($processedPayload, $webhook);
            }

            $result['queued'] = $shouldQueue;

            // Create event instance
            $event = $this->createEvent($eventClass, $processedPayload, $webhook);

            // Dispatch event
            if ($shouldQueue && $this->isQueueable($event)) {
                Event::dispatch($event);
                Log::info('Webhook event queued for processing', [
                    'event_class' => $eventClass,
                    'topic' => $processedPayload['topic'],
                    'entity_id' => $processedPayload['entity_id'],
                ]);
            } else {
                Event::dispatchNow($event);
                Log::info('Webhook event dispatched synchronously', [
                    'event_class' => $eventClass,
                    'topic' => $processedPayload['topic'],
                    'entity_id' => $processedPayload['entity_id'],
                ]);
            }

            $result['dispatched'] = true;

        } catch (\Exception $e) {
            Log::error('Failed to dispatch webhook event', [
                'error' => $e->getMessage(),
                'topic' => $processedPayload['topic'],
                'entity_id' => $processedPayload['entity_id'] ?? null,
            ]);

            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Determine the event class based on webhook topic and action
     *
     * @param  array<string, mixed>  $processedPayload
     */
    protected function determineEventClass(array $processedPayload): ?string
    {
        $topic = $processedPayload['topic'];
        $action = $processedPayload['action'];

        // First, try exact match with topic
        if (isset($this->eventMap[$topic])) {
            return $this->eventMap[$topic];
        }

        // Try to construct event name from entity and action
        $entity = $processedPayload['entity'];
        $constructedTopic = $entity.$action;

        if (isset($this->eventMap[$constructedTopic])) {
            return $this->eventMap[$constructedTopic];
        }

        // Try custom event map from config
        $customMap = config('exactonline-laravel-api.webhooks.event_map', []);

        if (isset($customMap[$topic])) {
            $eventClass = $customMap[$topic];

            if (class_exists($eventClass)) {
                return $eventClass;
            }

            Log::warning('Custom webhook event class not found', [
                'topic' => $topic,
                'class' => $eventClass,
            ]);
        }

        // Log unknown webhook topic
        Log::info('No specific event class for webhook topic', [
            'topic' => $topic,
            'entity' => $entity,
            'action' => $action,
        ]);

        return null;
    }

    /**
     * Create event instance
     *
     * @param  array<string, mixed>  $processedPayload
     */
    protected function createEvent(string $eventClass, array $processedPayload, ?ExactWebhook $webhook): object
    {
        // Check if event class accepts webhook in constructor
        $reflection = new \ReflectionClass($eventClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $eventClass;
        }

        $parameters = $constructor->getParameters();

        // Most webhook events will accept payload and optional webhook
        if (count($parameters) >= 2) {
            return new $eventClass($processedPayload, $webhook);
        } elseif (count($parameters) === 1) {
            return new $eventClass($processedPayload);
        } else {
            $event = new $eventClass;

            // Try to set properties if they exist
            if ($reflection->hasProperty('payload')) {
                $property = $reflection->getProperty('payload');
                if ($property->isPublic()) {
                    $event->payload = $processedPayload;
                }
            }

            if ($webhook !== null && $reflection->hasProperty('webhook')) {
                $property = $reflection->getProperty('webhook');
                if ($property->isPublic()) {
                    $event->webhook = $webhook;
                }
            }

            return $event;
        }
    }

    /**
     * Determine if event should be queued
     *
     * @param  array<string, mixed>  $processedPayload
     */
    protected function shouldQueueEvent(array $processedPayload, ?ExactWebhook $webhook): bool
    {
        // Check webhook-specific queue configuration
        if ($webhook !== null) {
            $metadata = $webhook->metadata ?? [];
            if (isset($metadata['queue'])) {
                return (bool) $metadata['queue'];
            }
        }

        // Check global webhook queue configuration
        $queueConfig = config('exactonline-laravel-api.webhooks.queue');

        if ($queueConfig !== null) {
            return (bool) $queueConfig;
        }

        // Default to not queuing for faster processing
        return false;
    }

    /**
     * Check if event implements ShouldQueue
     */
    protected function isQueueable(object $event): bool
    {
        return $event instanceof \Illuminate\Contracts\Queue\ShouldQueue;
    }

    /**
     * Register a custom event mapping
     *
     * @param  string  $topic  The webhook topic
     * @param  string  $eventClass  The event class to dispatch
     */
    public function registerEventMapping(string $topic, string $eventClass): void
    {
        $this->eventMap[$topic] = $eventClass;
    }

    /**
     * Get all registered event mappings
     *
     * @return array<string, string>
     */
    public function getEventMappings(): array
    {
        return array_merge(
            $this->eventMap,
            config('exactonline-laravel-api.webhooks.event_map', [])
        );
    }
}
