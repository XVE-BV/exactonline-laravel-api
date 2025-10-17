<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Exceptions\TokenRefreshException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

uses(RefreshDatabase::class);

class TestableRefreshAccessTokenAction extends RefreshAccessTokenAction
{
    public int $attempts = 0;

    public bool $alwaysFail = false;

    public ?array $nextTokens = null;

    public function tokenNeedsRefreshPublic(ExactConnection $connection): bool
    {
        return $this->tokenNeedsRefresh($connection);
    }

    public function performTokenRefreshWithRetryPublic(ExactConnection $connection, int $maxRetries = 3): array
    {
        return $this->performTokenRefreshWithRetry($connection, $maxRetries);
    }

    public function waitForRefreshAndReturnTokensPublic(ExactConnection $connection, int $maxWaitMs = 3000): array
    {
        return $this->waitForRefreshAndReturnTokens($connection, $maxWaitMs);
    }

    protected function performTokenRefresh(ExactConnection $connection): array
    {
        $this->attempts++;

        if ($this->alwaysFail) {
            throw new Exception('Simulated failure');
        }

        if ($this->attempts === 1 && $this->nextTokens === null) {
            // Fail first attempt by default, then succeed
            throw new Exception('Temporary error');
        }

        return $this->nextTokens ?? [
            'access_token' => 'refreshed-access',
            'refresh_token' => 'refreshed-refresh',
            'expires_at' => now()->addMinutes(10)->timestamp,
        ];
    }
}

it('determines token refresh need at 9-minute threshold', function () {
    $connection = ExactConnection::create([
        'client_id' => 'id',
        'client_secret' => 'secret',
        'redirect_url' => 'https://app.test/callback',
        'base_url' => 'https://start.exactonline.nl',
        'is_active' => true,
        'token_expires_at' => now()->addSeconds(541)->timestamp,
    ]);

    $action = new TestableRefreshAccessTokenAction;

    expect($action->tokenNeedsRefreshPublic($connection))->toBeFalse();

    $connection->update(['token_expires_at' => now()->addSeconds(539)->timestamp]);
    expect($action->tokenNeedsRefreshPublic($connection))->toBeTrue();
});

it('retries with exponential backoff and eventually succeeds', function () {
    $connection = ExactConnection::create([
        'client_id' => 'id',
        'client_secret' => 'secret',
        'redirect_url' => 'https://app.test/callback',
        'base_url' => 'https://start.exactonline.nl',
        'is_active' => true,
        'refresh_token' => 'refresh-existing',
    ]);

    $action = new TestableRefreshAccessTokenAction;

    $tokens = $action->performTokenRefreshWithRetryPublic($connection, 3);

    expect($action->attempts)->toBe(2);
    expect($tokens['access_token'])->toBe('refreshed-access');
    expect($tokens['refresh_token'])->toBe('refreshed-refresh');
});

it('throws after max retries are exceeded', function () {
    $connection = ExactConnection::create([
        'client_id' => 'id',
        'client_secret' => 'secret',
        'redirect_url' => 'https://app.test/callback',
        'base_url' => 'https://start.exactonline.nl',
        'is_active' => true,
        'refresh_token' => 'refresh-existing',
    ]);

    $action = new TestableRefreshAccessTokenAction;
    $action->alwaysFail = true;

    $action->performTokenRefreshWithRetryPublic($connection, 2);
})->throws(TokenRefreshException::class);

it('waits for other process and returns tokens when already refreshed', function () {
    // Start with no need to refresh
    $connection = ExactConnection::create([
        'client_id' => 'id',
        'client_secret' => 'secret',
        'redirect_url' => 'https://app.test/callback',
        'base_url' => 'https://start.exactonline.nl',
        'is_active' => true,
        'access_token' => 'current-access',
        'refresh_token' => 'current-refresh',
        'token_expires_at' => now()->addMinutes(10)->timestamp,
    ]);

    $action = new TestableRefreshAccessTokenAction;

    $result = $action->waitForRefreshAndReturnTokensPublic($connection, 200);

    expect($result['access_token'])->toBe('current-access');
    expect($result['refresh_token'])->toBe('current-refresh');
});

it('throws a lock timeout when token remains stale beyond wait time', function () {
    // Needs refresh and will stay that way
    $connection = ExactConnection::create([
        'client_id' => 'id',
        'client_secret' => 'secret',
        'redirect_url' => 'https://app.test/callback',
        'base_url' => 'https://start.exactonline.nl',
        'is_active' => true,
        'access_token' => 'stale-access',
        'refresh_token' => 'stale-refresh',
        'token_expires_at' => now()->subMinute()->timestamp,
    ]);

    $action = new TestableRefreshAccessTokenAction;

    $action->waitForRefreshAndReturnTokensPublic($connection, 200);
})->throws(TokenRefreshException::class);
