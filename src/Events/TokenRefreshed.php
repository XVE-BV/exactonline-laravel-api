<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class TokenRefreshed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ExactConnection $connection
    ) {}
}
