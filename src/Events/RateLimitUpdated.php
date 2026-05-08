<?php

declare(strict_types=1);

namespace XVE\Exactonline\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use XVE\Exactonline\Models\ExactConnection;
use XVE\Exactonline\Models\ExactRateLimit;

class RateLimitUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ExactConnection $connection,
        public ExactRateLimit $rateLimit
    ) {}

    /**
     * Get the daily usage percentage.
     */
    public function getDailyUsagePercentage(): ?float
    {
        return $this->rateLimit->getDailyUsagePercentage();
    }

    /**
     * Check if daily limit is critical (>90% used).
     */
    public function isDailyCritical(): bool
    {
        $usage = $this->getDailyUsagePercentage();

        return $usage !== null && $usage > 90;
    }
}
