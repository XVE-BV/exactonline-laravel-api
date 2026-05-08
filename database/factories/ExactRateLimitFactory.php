<?php

declare(strict_types=1);

namespace XVE\Exactonline\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XVE\Exactonline\Models\ExactConnection;
use XVE\Exactonline\Models\ExactRateLimit;

/**
 * @extends Factory<ExactRateLimit>
 */
class ExactRateLimitFactory extends Factory
{
    protected $model = ExactRateLimit::class;

    public function definition(): array
    {
        return [
            'connection_id' => ExactConnection::factory(),
            'daily_limit' => 5000,
            'daily_remaining' => 4500,
            'daily_reset_at' => now()->addHours(12)->getTimestamp(),
            'minutely_limit' => 60,
            'minutely_remaining' => 55,
            'minutely_reset_at' => now()->addSeconds(60)->getTimestamp(),
            'last_checked_at' => now(),
            'total_calls_today' => 0,
        ];
    }
}
