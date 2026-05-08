<?php

declare(strict_types=1);

namespace XVE\Exactonline\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use XVE\Exactonline\Models\ExactConnection;

class TokenAcquired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ExactConnection $connection
    ) {}
}
