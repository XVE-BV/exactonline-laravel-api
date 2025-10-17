<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\OAuth;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Events\RefreshTokenExpiringSoon;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class MonitorRefreshTokenExpiryAction
{
    /**
     * Monitor refresh token expiry and dispatch warning events
     *
     * This action checks all active connections for refresh tokens that are
     * expiring soon and dispatches events at configurable thresholds (7, 14, 28 days).
     *
     * @param  int|null  $connectionId  Optional specific connection to check
     * @param  array<int>  $warningDays  Days before expiry to trigger warnings
     * @return Collection<int, ExactConnection>
     */
    public function execute(
        ?int $connectionId = null,
        array $warningDays = [7, 14, 28]
    ): Collection {
        // Get connections to check
        $connections = $this->getConnectionsToCheck($connectionId);

        $expiringConnections = collect();

        foreach ($connections as $connection) {
            $daysUntilExpiry = $this->getDaysUntilExpiry($connection);

            if ($daysUntilExpiry === null) {
                continue;
            }

            // Check against warning thresholds
            foreach ($warningDays as $threshold) {
                if ($daysUntilExpiry <= $threshold) {
                    $this->handleExpiringToken($connection, $daysUntilExpiry, $threshold);
                    $expiringConnections->push($connection);
                    break; // Only trigger one warning per connection
                }
            }
        }

        Log::info('Refresh token expiry monitoring completed', [
            'connections_checked' => $connections->count(),
            'expiring_connections' => $expiringConnections->count(),
        ]);

        return $expiringConnections;
    }

    /**
     * Get connections to check for token expiry
     *
     * @return Collection<int, ExactConnection>
     */
    protected function getConnectionsToCheck(?int $connectionId): Collection
    {
        $query = ExactConnection::active()
            ->whereNotNull('refresh_token')
            ->whereNotNull('refresh_token_expires_at');

        if ($connectionId !== null) {
            $query->where('id', $connectionId);
        }

        return $query->get();
    }

    /**
     * Calculate days until refresh token expires
     */
    protected function getDaysUntilExpiry(ExactConnection $connection): ?int
    {
        if (! $connection->refresh_token_expires_at) {
            return null;
        }

        $expiresAt = \Carbon\Carbon::createFromTimestamp($connection->refresh_token_expires_at);
        $now = now();

        // If already expired
        if ($expiresAt->isPast()) {
            return 0;
        }

        return (int) $now->diffInDays($expiresAt, false);
    }

    /**
     * Handle an expiring refresh token
     */
    protected function handleExpiringToken(
        ExactConnection $connection,
        int $daysUntilExpiry,
        int $warningThreshold
    ): void {
        // Dispatch warning event
        event(new RefreshTokenExpiringSoon(
            $connection,
            $daysUntilExpiry,
            $warningThreshold
        ));

        // Log the warning
        $logLevel = match (true) {
            $daysUntilExpiry === 0 => 'critical',
            $daysUntilExpiry <= 3 => 'error',
            $daysUntilExpiry <= 7 => 'warning',
            default => 'info',
        };

        Log::log($logLevel, 'Refresh token expiring soon', [
            'connection_id' => $connection->id,
            'connection_name' => $connection->name,
            'days_until_expiry' => $daysUntilExpiry,
            'warning_threshold' => $warningThreshold,
            'expires_at' => $connection->refresh_token_expires_at,
        ]);

        // Update connection metadata to track warning
        $metadata = $connection->metadata ?? [];
        $metadata['last_expiry_warning'] = [
            'timestamp' => now()->toIso8601String(),
            'days_remaining' => $daysUntilExpiry,
            'threshold' => $warningThreshold,
        ];
        $connection->update(['metadata' => $metadata]);
    }
}
