<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $connection_id
 * @property int $code
 * @property string|null $description
 * @property string|null $hid
 * @property string|null $customer_code
 * @property string|null $customer_name
 * @property string|null $country
 * @property string|null $currency
 * @property string|null $vat_number
 * @property bool $is_main
 * @property int $status
 * @property int $blocking_status
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $synced_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ExactConnection $connection
 */
class ExactDivision extends Model
{
    protected $table = 'exact_divisions';

    protected $fillable = [
        'connection_id',
        'code',
        'description',
        'hid',
        'customer_code',
        'customer_name',
        'country',
        'currency',
        'vat_number',
        'is_main',
        'status',
        'blocking_status',
        'started_at',
        'archived_at',
        'synced_at',
    ];

    protected $casts = [
        'code' => 'integer',
        'is_main' => 'boolean',
        'status' => 'integer',
        'blocking_status' => 'integer',
        'started_at' => 'datetime',
        'archived_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the connection that owns this division.
     *
     * @return BelongsTo<ExactConnection, $this>
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(ExactConnection::class, 'connection_id');
    }

    /**
     * Scope a query to only include active divisions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ExactDivision>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ExactDivision>
     */
    public function scopeActive($query)
    {
        return $query->where('status', 0)->where('blocking_status', 0);
    }

    /**
     * Scope a query to only include the main division.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ExactDivision>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ExactDivision>
     */
    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    /**
     * Get the display name for this division.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->description ?? "Division {$this->code}";

        if ($this->customer_name) {
            $name .= " ({$this->customer_name})";
        }

        return $name;
    }

    /**
     * Check if the division is active (not archived or blocked).
     */
    public function isActive(): bool
    {
        return $this->status === 0 && $this->blocking_status === 0;
    }

    /**
     * Check if the division is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === 1;
    }

    /**
     * Check if the division is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->blocking_status !== 0;
    }
}
