<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\MonitorRefreshTokenExpiryAction;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Exceptions\TokenRefreshException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class RefreshTokensCommand extends Command
{
    protected $signature = 'exact:refresh-tokens';

    protected $description = 'Refresh access tokens for active Exact Online connections and warn about expiring refresh tokens';

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
        $needsRefresh = empty($connection->token_expires_at)
            || $connection->token_expires_at < (now()->timestamp + 540);

        if (! $needsRefresh) {
            $this->line("  [{$connection->name}] Token still valid, skipping.");

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
