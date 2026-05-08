<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\Webhooks;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use XVE\ExactonlineLaravelApi\Events\Webhooks\AccountCreated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\AccountDeleted;
use XVE\ExactonlineLaravelApi\Events\Webhooks\AccountUpdated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\ContactCreated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\ContactDeleted;
use XVE\ExactonlineLaravelApi\Events\Webhooks\ContactUpdated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\DocumentCreated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\DocumentDeleted;
use XVE\ExactonlineLaravelApi\Events\Webhooks\DocumentUpdated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\FinancialTransactionCreated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\FinancialTransactionUpdated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\GenericWebhookReceived;
use XVE\ExactonlineLaravelApi\Events\Webhooks\GLAccountCreated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\GLAccountUpdated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\ItemCreated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\ItemDeleted;
use XVE\ExactonlineLaravelApi\Events\Webhooks\ItemUpdated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\ProjectCreated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\ProjectDeleted;
use XVE\ExactonlineLaravelApi\Events\Webhooks\ProjectUpdated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\PurchaseInvoiceCreated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\PurchaseInvoiceUpdated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\SalesInvoiceCreated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\SalesInvoiceDeleted;
use XVE\ExactonlineLaravelApi\Events\Webhooks\SalesInvoiceUpdated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\SalesOrderCreated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\SalesOrderUpdated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\StockPositionUpdated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\SubscriptionCreated;
use XVE\ExactonlineLaravelApi\Events\Webhooks\SubscriptionDeleted;
use XVE\ExactonlineLaravelApi\Events\Webhooks\SubscriptionUpdated;
use XVE\ExactonlineLaravelApi\Models\ExactWebhook;

class DispatchWebhookEventAction
{
    /**
     * Map of webhook topics to event classes
     *
     * @var array<string, string>
     */
    protected array $eventMap = [
        // Account events
        'AccountsCreated' => AccountCreated::class,
        'AccountsUpdated' => AccountUpdated::class,
        'AccountsDeleted' => AccountDeleted::class,

        // Sales Invoice events
        'SalesInvoicesCreated' => SalesInvoiceCreated::class,
        'SalesInvoicesUpdated' => SalesInvoiceUpdated::class,
        'SalesInvoicesDeleted' => SalesInvoiceDeleted::class,

        // Contact events
        'ContactsCreated' => ContactCreated::class,
        'ContactsUpdated' => ContactUpdated::class,
        'ContactsDeleted' => ContactDeleted::class,

        // Document events
        'DocumentsCreated' => DocumentCreated::class,
        'DocumentsUpdated' => DocumentUpdated::class,
        'DocumentsDeleted' => DocumentDeleted::class,

        // GL Account events
        'GLAccountsCreated' => GLAccountCreated::class,
        'GLAccountsUpdated' => GLAccountUpdated::class,

        // Financial Transaction events
        'FinancialTransactionsCreated' => FinancialTransactionCreated::class,
        'FinancialTransactionsUpdated' => FinancialTransactionUpdated::class,

        // Item events
        'ItemsCreated' => ItemCreated::class,
        'ItemsUpdated' => ItemUpdated::class,
        'ItemsDeleted' => ItemDeleted::class,

        // Project events
        'ProjectsCreated' => ProjectCreated::class,
        'ProjectsUpdated' => ProjectUpdated::class,
        'ProjectsDeleted' => ProjectDeleted::class,

        // Purchase Invoice events
        'PurchaseInvoicesCreated' => PurchaseInvoiceCreated::class,
        'PurchaseInvoicesUpdated' => PurchaseInvoiceUpdated::class,

        // Sales Order events
        'SalesOrdersCreated' => SalesOrderCreated::class,
        'SalesOrdersUpdated' => SalesOrderUpdated::class,

        // Stock Position events
        'StockPositionsUpdated' => StockPositionUpdated::class,

        // Subscription events
        'SubscriptionsCreated' => SubscriptionCreated::class,
        'SubscriptionsUpdated' => SubscriptionUpdated::class,
        'SubscriptionsDeleted' => SubscriptionDeleted::class,
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
                $eventClass = GenericWebhookReceived::class;
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
                Event::dispatch($event);
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
        /** @var class-string $eventClass */
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
                    // @phpstan-ignore-next-line property.notFound
                    $event->payload = $processedPayload;
                }
            }

            if ($webhook !== null && $reflection->hasProperty('webhook')) {
                $property = $reflection->getProperty('webhook');
                if ($property->isPublic()) {
                    // @phpstan-ignore-next-line property.notFound
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
        return $event instanceof ShouldQueue;
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
