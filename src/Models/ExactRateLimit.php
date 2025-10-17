<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $connection_id
 * @property int|null $daily_limit
 * @property int|null $daily_remaining
 * @property int|null $daily_reset_at
 * @property int|null $minutely_limit
 * @property int|null $minutely_remaining
 * @property int|null $minutely_reset_at
 * @property \Illuminate\Support\Carbon|null $last_checked_at
 * @property int $total_calls_today
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ExactConnection $connection
 */
class ExactRateLimit extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'exact_rate_limits';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'connection_id',
        'daily_limit',
        'daily_remaining',
        'daily_reset_at',
        'minutely_limit',
        'minutely_remaining',
        'minutely_reset_at',
        'last_checked_at',
        'total_calls_today',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'daily_limit' => 'integer',
        'daily_remaining' => 'integer',
        'daily_reset_at' => 'integer',
        'minutely_limit' => 'integer',
        'minutely_remaining' => 'integer',
        'minutely_reset_at' => 'integer',
        'last_checked_at' => 'datetime',
        'total_calls_today' => 'integer',
    ];

    /**
     * Get the connection that owns the rate limit.
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(ExactConnection::class, 'connection_id');
    }

    /**
     * Check if the daily limit has been exceeded.
     */
    public function isDailyLimitExceeded(): bool
    {
        if ($this->daily_remaining === null) {
            return false;
        }

        return $this->daily_remaining <= 0;
    }

    /**
     * Check if the minutely limit has been exceeded.
     */
    public function isMinutelyLimitExceeded(): bool
    {
        if ($this->minutely_remaining === null) {
            return false;
        }

        return $this->minutely_remaining <= 0;
    }

    /**
     * Get seconds until daily limit resets.
     */
    public function secondsUntilDailyReset(): ?int
    {
        if (! $this->daily_reset_at) {
            return null;
        }

        $secondsRemaining = $this->daily_reset_at - now()->timestamp;

        return max(0, $secondsRemaining);
    }

    /**
     * Get seconds until minutely limit resets.
     */
    public function secondsUntilMinutelyReset(): ?int
    {
        if (! $this->minutely_reset_at) {
            return null;
        }

        $secondsRemaining = $this->minutely_reset_at - now()->timestamp;

        return max(0, $secondsRemaining);
    }

    /**
     * Update rate limits from API response headers.
     *
     * @param  array<string, string>  $headers
     */
    public function updateFromHeaders(array $headers): void
    {
        $updates = [
            'last_checked_at' => now(),
        ];

        // Daily limits
        if (isset($headers['X-RateLimit-Limit'])) {
            $updates['daily_limit'] = (int) $headers['X-RateLimit-Limit'];
        }

        if (isset($headers['X-RateLimit-Remaining'])) {
            $updates['daily_remaining'] = (int) $headers['X-RateLimit-Remaining'];
        }

        if (isset($headers['X-RateLimit-Reset'])) {
            // Exact Online returns reset time in milliseconds
            $ms = (int) $headers['X-RateLimit-Reset'];
            $updates['daily_reset_at'] = (int) ($ms / 1000);
        }

        // Minutely limits
        if (isset($headers['X-RateLimit-Minutely-Limit'])) {
            $updates['minutely_limit'] = (int) $headers['X-RateLimit-Minutely-Limit'];
        }

        if (isset($headers['X-RateLimit-Minutely-Remaining'])) {
            $updates['minutely_remaining'] = (int) $headers['X-RateLimit-Minutely-Remaining'];
        }

        if (isset($headers['X-RateLimit-Minutely-Reset'])) {
            // Exact Online returns reset time in milliseconds
            $ms = (int) $headers['X-RateLimit-Minutely-Reset'];
            $updates['minutely_reset_at'] = (int) ($ms / 1000);
        }

        $this->update($updates);
    }

    /**
     * Increment the call counter.
     */
    public function incrementCallCounter(): void
    {
        // Reset counter if it's a new day
        if ($this->shouldResetDailyCounter()) {
            $this->total_calls_today = 0;
        }

        $this->increment('total_calls_today');
    }

    /**
     * Check if the daily counter should be reset.
     */
    protected function shouldResetDailyCounter(): bool
    {
        if (! $this->last_checked_at) {
            return true;
        }

        return ! $this->last_checked_at->isToday();
    }

    /**
     * Check if we're approaching the rate limit.
     *
     * @param  float  $threshold  Percentage threshold (e.g., 0.9 for 90%)
     */
    public function isApproachingDailyLimit(float $threshold = 0.9): bool
    {
        if (! $this->daily_limit || ! $this->daily_remaining) {
            return false;
        }

        $usedPercentage = 1 - ($this->daily_remaining / $this->daily_limit);

        return $usedPercentage >= $threshold;
    }

    /**
     * Get percentage of daily limit used.
     */
    public function getDailyUsagePercentage(): ?float
    {
        if (! $this->daily_limit || ! $this->daily_remaining) {
            return null;
        }

        $used = $this->daily_limit - $this->daily_remaining;

        return ($used / $this->daily_limit) * 100;
    }
}
