<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use XVE\ExactonlineLaravelApi\Actions\OAuth\MonitorRefreshTokenExpiryAction;
use XVE\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use XVE\ExactonlineLaravelApi\Exceptions\TokenRefreshException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;
use XVE\ExactonlineLaravelApi\Support\Config;

class RefreshTokensCommand extends Command
{
    protected $signature = 'exact:refresh-tokens';

    protected $description = 'Keep Exact Online refresh tokens alive: refresh a connection only when its refresh token nears its ~30-day expiry (access tokens are refreshed lazily per request)';

    public function handle(): int
    {
        $connections = ExactConnection::active()
            ->whereNotNull('refresh_token')
            ->get();

        if ($connections->isEmpty()) {
            $this->info('No active authenticated connections found.');

            return self::SUCCESS;
        }

        $this->info("Checking {$connections->count()} connection(s)...");

        $refreshAction = Config::getAction('refresh_access_token', RefreshAccessTokenAction::class);

        foreach ($connections as $connection) {
            $this->refreshAccessToken($connection, $refreshAction);
        }

        $this->monitorRefreshTokenExpiry();

        return self::SUCCESS;
    }

    protected function refreshAccessToken(ExactConnection $connection, RefreshAccessTokenAction $action): void
    {
        // picqer refreshes the 10-minute access token lazily on every request,
        // so this scheduled run only needs to keep the single-use refresh token
        // from reaching its ~30-day idle expiry. Refresh only when it is within
        // the configured buffer; every refresh rotates the refresh token, and
        // each rotation risks breaking the chain, so avoid needless ones.
        $bufferDays = Config::getRefreshTokenBufferDays();
        $needsRefresh = empty($connection->refresh_token_expires_at)
            || $connection->refreshTokenExpiringSoon($bufferDays);

        if (! $needsRefresh) {
            $this->line("  [{$connection->name}] Refresh token not near expiry, skipping.");

            return;
        }

        try {
            $action->execute($connection);
            $this->info("  [{$connection->name}] Access token refreshed successfully.");
        } catch (TokenRefreshException $e) {
            $this->error("  [{$connection->name}] Refresh failed: {$e->getMessage()}");
        }
    }

    protected function monitorRefreshTokenExpiry(): void
    {
        $monitorAction = Config::getAction(
            'monitor_refresh_token_expiry',
            MonitorRefreshTokenExpiryAction::class
        );

        $expiring = $monitorAction->execute();

        if ($expiring->isNotEmpty()) {
            $this->newLine();
            $this->warn("⚠ {$expiring->count()} connection(s) have refresh tokens expiring soon:");

            foreach ($expiring as $connection) {
                $daysLeft = $connection->refresh_token_expires_at
                    ? (int) now()->diffInDays(Carbon::createFromTimestamp($connection->refresh_token_expires_at), false)
                    : 0;

                $this->warn("  [{$connection->name}] {$daysLeft} day(s) remaining — re-authenticate to renew.");
            }
        }
    }
}
