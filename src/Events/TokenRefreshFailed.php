<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class TokenRefreshFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ExactConnection $connection,
        public \Throwable $exception
    ) {}
}
