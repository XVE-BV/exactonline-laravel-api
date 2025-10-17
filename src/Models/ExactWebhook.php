<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $connection_id
 * @property string|null $webhook_id
 * @property string $topic
 * @property string $callback_url
 * @property string|null $webhook_secret
 * @property bool $is_active
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $last_received_at
 * @property int $events_received
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ExactConnection $connection
 */
class ExactWebhook extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'exact_webhooks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'connection_id',
        'webhook_id',
        'topic',
        'callback_url',
        'webhook_secret',
        'is_active',
        'metadata',
        'last_received_at',
        'events_received',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'last_received_at' => 'datetime',
        'events_received' => 'integer',
    ];

    /**
     * Available webhook topics from Exact Online.
     *
     * @var array<string>
     */
    public const TOPICS = [
        'Accounts',
        'BankAccounts',
        'Contacts',
        'CostCenters',
        'CostUnits',
        'Documents',
        'DocumentTypes',
        'FinancialPeriods',
        'GLAccounts',
        'Items',
        'ItemGroups',
        'Journals',
        'Layouts',
        'Opportunities',
        'PaymentConditions',
        'Projects',
        'PurchaseInvoices',
        'Quotations',
        'SalesInvoices',
        'SalesOrders',
        'StockPositions',
        'Subscriptions',
        'TransactionLines',
        'Users',
        'VATCodes',
        'Warehouses',
        'WebhookSubscriptions',
    ];

    /**
     * Get the connection that owns the webhook.
     *
     * @return BelongsTo<ExactConnection, $this>
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(ExactConnection::class, 'connection_id');
    }

    /**
     * Scope a query to only include active webhooks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ExactWebhook>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ExactWebhook>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by topic.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ExactWebhook>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ExactWebhook>
     */
    public function scopeByTopic($query, string $topic)
    {
        return $query->where('topic', $topic);
    }

    /**
     * Scope a query to filter by multiple topics.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ExactWebhook>  $query
     * @param  array<string>  $topics
     * @return \Illuminate\Database\Eloquent\Builder<ExactWebhook>
     */
    public function scopeByTopics($query, array $topics)
    {
        return $query->whereIn('topic', $topics);
    }

    /**
     * Mark that a webhook event was received.
     */
    public function markEventReceived(): void
    {
        $this->increment('events_received');
        $this->update(['last_received_at' => now()]);
    }

    /**
     * Check if this topic is valid.
     */
    public static function isValidTopic(string $topic): bool
    {
        return in_array($topic, self::TOPICS, true);
    }

    /**
     * Get the event class name for this webhook topic.
     */
    public function getEventClassName(): string
    {
        $action = match ($this->metadata['action'] ?? 'default') {
            'create' => 'Created',
            'update' => 'Updated',
            'delete' => 'Deleted',
            default => 'Changed',
        };

        return "Skylence\\ExactonlineLaravelApi\\Events\\Webhooks\\{$this->topic}{$action}";
    }

    /**
     * Verify the webhook signature.
     */
    public function verifySignature(string $payload, string $signature): bool
    {
        if (! $this->webhook_secret) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->webhook_secret);

        return hash_equals($expectedSignature, $signature);
    }
}
