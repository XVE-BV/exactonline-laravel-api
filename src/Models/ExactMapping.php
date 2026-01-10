<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Skylence\ExactonlineLaravelApi\Support\Config;

/**
 * Polymorphic mapping between local models and Exact Online entities.
 *
 * @property int $id
 * @property string $mappable_type
 * @property int $mappable_id
 * @property int $connection_id
 * @property string $division
 * @property string $environment
 * @property string $exact_id
 * @property string|null $exact_code
 * @property string $reference_type
 * @property \Illuminate\Support\Carbon|null $synced_at
 * @property string|null $last_error
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model $mappable
 * @property-read ExactConnection $connection
 */
class ExactMapping extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'exact_mappings';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'mappable_type',
        'mappable_id',
        'connection_id',
        'division',
        'environment',
        'exact_id',
        'exact_code',
        'reference_type',
        'synced_at',
        'last_error',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'synced_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'reference_type' => 'primary',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ExactMapping $mapping): void {
            if (! $mapping->environment) {
                $mapping->environment = Config::getMappingEnvironment();
            }
        });
    }

    /**
     * Get the parent mappable model.
     *
     * @return MorphTo<Model, $this>
     */
    public function mappable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the Exact connection for this mapping.
     *
     * @return BelongsTo<ExactConnection, $this>
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(ExactConnection::class, 'connection_id');
    }

    /**
     * Scope a query to filter by connection.
     *
     * @param  Builder<ExactMapping>  $query
     * @return Builder<ExactMapping>
     */
    public function scopeForConnection(Builder $query, ExactConnection $connection): Builder
    {
        return $query->where('connection_id', $connection->id);
    }

    /**
     * Scope a query to filter by division.
     *
     * @param  Builder<ExactMapping>  $query
     * @return Builder<ExactMapping>
     */
    public function scopeForDivision(Builder $query, string $division): Builder
    {
        return $query->where('division', $division);
    }

    /**
     * Scope a query to filter by reference type.
     *
     * @param  Builder<ExactMapping>  $query
     * @return Builder<ExactMapping>
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('reference_type', $type);
    }

    /**
     * Scope a query to filter by exact ID.
     *
     * @param  Builder<ExactMapping>  $query
     * @return Builder<ExactMapping>
     */
    public function scopeForExactId(Builder $query, string $exactId): Builder
    {
        return $query->where('exact_id', $exactId);
    }

    /**
     * Scope a query to filter by environment.
     *
     * @param  Builder<ExactMapping>  $query
     * @return Builder<ExactMapping>
     */
    public function scopeForEnvironment(Builder $query, ?string $environment = null): Builder
    {
        return $query->where('environment', $environment ?? Config::getMappingEnvironment());
    }

    /**
     * Scope a query to filter by current environment.
     *
     * @param  Builder<ExactMapping>  $query
     * @return Builder<ExactMapping>
     */
    public function scopeCurrentEnvironment(Builder $query): Builder
    {
        return $query->forEnvironment(Config::getMappingEnvironment());
    }

    /**
     * Generate the Exact Online URL for this mapping.
     */
    public function getExactUrl(): ?string
    {
        if (! $this->exact_id || ! $this->connection) {
            return null;
        }

        $baseUrl = rtrim($this->connection->base_url, '/');
        $division = $this->division;

        return "{$baseUrl}/#/app/{$division}/entities/{$this->exact_id}";
    }

    /**
     * Mark this mapping as synced.
     */
    public function markAsSynced(): void
    {
        $this->update([
            'synced_at' => now(),
            'last_error' => null,
        ]);
    }

    /**
     * Record a sync error.
     */
    public function recordError(string $error): void
    {
        $this->update([
            'last_error' => $error,
        ]);
    }

    /**
     * Check if this mapping has a sync error.
     */
    public function hasError(): bool
    {
        return $this->last_error !== null;
    }

    /**
     * Check if this mapping was synced recently.
     */
    public function wasSyncedRecently(int $minutes = 60): bool
    {
        if (! $this->synced_at) {
            return false;
        }

        return $this->synced_at->isAfter(now()->subMinutes($minutes));
    }
}
