<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class RefreshTokenExpiringSoon
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param ExactConnection $connection
     * @param int $daysUntilExpiry
     * @param int $warningThreshold
     */
    public function __construct(
        public ExactConnection $connection,
        public int $daysUntilExpiry,
        public int $warningThreshold
    ) {
    }

    /**
     * Check if the token is critically close to expiring.
     *
     * @return bool
     */
    public function isCritical(): bool
    {
        return $this->daysUntilExpiry <= 3;
    }

    /**
     * Get a human-readable message about the expiry.
     *
     * @return string
     */
    public function getMessage(): string
    {
        if ($this->daysUntilExpiry === 0) {
            return "Refresh token has expired for connection {$this->connection->name}. User must re-authenticate immediately.";
        }

        if ($this->daysUntilExpiry === 1) {
            return "Refresh token expires tomorrow for connection {$this->connection->name}. User should re-authenticate soon.";
        }

        return "Refresh token expires in {$this->daysUntilExpiry} days for connection {$this->connection->name}.";
    }
}
