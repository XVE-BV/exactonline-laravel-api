<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Models\ExactRateLimit;

class RateLimitApproaching
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array<string>  $warnings
     */
    public function __construct(
        public ExactConnection $connection,
        public ExactRateLimit $rateLimit,
        public array $warnings
    ) {}

    /**
     * Get the most critical warning.
     */
    public function getMostCriticalWarning(): ?string
    {
        return $this->warnings[0] ?? null;
    }

    /**
     * Check if this is a critical warning (daily limit >90% or minutely <10 remaining).
     */
    public function isCritical(): bool
    {
        // Check if daily limit is critical
        if ($this->rateLimit->isApproachingDailyLimit(0.9)) {
            return true;
        }

        // Check if minutely limit is critical
        if ($this->rateLimit->minutely_remaining !== null && $this->rateLimit->minutely_remaining < 10) {
            return true;
        }

        return false;
    }

    /**
     * Get a formatted message about the rate limit status.
     */
    public function getMessage(): string
    {
        $parts = [];

        if ($this->rateLimit->daily_remaining !== null) {
            $parts[] = sprintf(
                'Daily: %d/%d remaining',
                $this->rateLimit->daily_remaining,
                $this->rateLimit->daily_limit ?? 0
            );
        }

        if ($this->rateLimit->minutely_remaining !== null) {
            $parts[] = sprintf(
                'Minutely: %d/%d remaining',
                $this->rateLimit->minutely_remaining,
                $this->rateLimit->minutely_limit ?? 60
            );
        }

        return 'Rate limit approaching for connection '.$this->connection->name.': '.implode(', ', $parts);
    }
}
