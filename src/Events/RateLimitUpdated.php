<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Models\ExactRateLimit;

class RateLimitUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param ExactConnection $connection
     * @param ExactRateLimit $rateLimit
     */
    public function __construct(
        public ExactConnection $connection,
        public ExactRateLimit $rateLimit
    ) {
    }

    /**
     * Get the daily usage percentage.
     *
     * @return float|null
     */
    public function getDailyUsagePercentage(): ?float
    {
        return $this->rateLimit->getDailyUsagePercentage();
    }

    /**
     * Check if daily limit is critical (>90% used).
     *
     * @return bool
     */
    public function isDailyCritical(): bool
    {
        $usage = $this->getDailyUsagePercentage();
        return $usage !== null && $usage > 90;
    }
}
