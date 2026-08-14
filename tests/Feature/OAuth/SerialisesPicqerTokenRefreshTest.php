<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Picqer\Financials\Exact\Connection;
use XVE\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

/**
 * Exact's refresh token is single-use. Two workers that hit an expired access
 * token at the same moment both POST the same refresh token: one wins, the
 * other gets invalid_grant and its API call fails.
 *
 * picqer exposes three hooks around its own token acquisition —
 * acquireAccessTokenLock / Unlock and refreshAccessToken. Registering the lock
 * pair serialises the two workers, and the refresh hook then hands the waiter
 * the winner's freshly stored token so it skips its own request entirely.
 *
 * The lock key must NOT be RefreshAccessTokenAction's own
 * ("exact-token-refresh:{id}"): that action builds a picqer connection inside
 * performTokenRefresh(), so sharing the key would deadlock it against itself.
 * Several cases below pin exactly that.
 */
function picqerCallback(Connection $picqer, string $name): ?callable
{
    $property = (new ReflectionClass($picqer))->getProperty($name);
    $property->setAccessible(true);

    return $property->getValue($picqer);
}

it('registers the lock and refresh callbacks picqer needs to serialise', function () {
    $connection = ExactConnection::factory()->create(['is_active' => true]);

    $picqer = $connection->getPicqerConnection();

    expect(picqerCallback($picqer, 'acquireAccessTokenLockCallback'))->toBeCallable()
        ->and(picqerCallback($picqer, 'acquireAccessTokenUnlockCallback'))->toBeCallable()
        ->and(picqerCallback($picqer, 'refreshAccessTokenCallback'))->toBeCallable();
});

it('does not reuse the refresh action lock key, which would deadlock it', function () {
    $connection = ExactConnection::factory()->create(['is_active' => true]);

    // RefreshAccessTokenAction::execute() holds this for the whole refresh and
    // calls getPicqerConnection() while still holding it.
    $actionLock = Cache::lock("exact-token-refresh:{$connection->id}", 30);
    expect($actionLock->get())->toBeTrue();

    $startedAt = microtime(true);

    try {
        $picqer = $connection->getPicqerConnection();

        // This is the nested acquisition the action itself provokes.
        (picqerCallback($picqer, 'acquireAccessTokenLockCallback'))();
        (picqerCallback($picqer, 'acquireAccessTokenUnlockCallback'))();
    } finally {
        $actionLock->release();
    }

    $elapsed = microtime(true) - $startedAt;

    // Asserted on DURATION, not on an exception: acquirePicqerRefreshLock()
    // deliberately swallows a lock timeout and proceeds unsynchronised, so a
    // shared key does not throw — it just sits on Lock::block() for its full
    // timeout first. Measured: ~0.2s with distinct keys, ~15s with the action's
    // own key. Every real Exact call made while the action holds its lock would
    // pay that stall.
    expect($elapsed)->toBeLessThan(2.0);
});

it('hands a waiting worker the token another worker just stored', function () {
    $connection = ExactConnection::factory()->create([
        'is_active' => true,
        'access_token' => 'stale-access-token',
        'refresh_token' => 'stale-refresh-token',
        'token_expires_at' => now()->subMinute()->timestamp,
    ]);

    $picqer = $connection->getPicqerConnection();

    // Another worker wins the race and stores a fresh pair.
    $connection->update([
        'access_token' => 'winner-access-token',
        'refresh_token' => 'winner-refresh-token',
        'token_expires_at' => now()->addMinutes(10)->timestamp,
    ]);

    (picqerCallback($picqer, 'refreshAccessTokenCallback'))($picqer);

    // picqer checks tokenHasExpired() straight after this and skips its own
    // request when the token is live — which is what stops the waiter from
    // burning the already-consumed refresh token.
    expect($picqer->getAccessToken())->toBe('winner-access-token')
        ->and($picqer->getRefreshToken())->toBe('winner-refresh-token');
});

it('leaves picqer alone when the stored token is still expired', function () {
    $connection = ExactConnection::factory()->create([
        'is_active' => true,
        'access_token' => 'stored-access-token',
        'refresh_token' => 'stored-refresh-token',
        'token_expires_at' => now()->subMinute()->timestamp,
    ]);

    $picqer = $connection->getPicqerConnection();
    $picqer->setAccessToken('in-flight-access-token');

    // Nobody refreshed, so there is nothing to adopt: picqer must go on to do
    // its own refresh rather than be downgraded to the stale stored row.
    (picqerCallback($picqer, 'refreshAccessTokenCallback'))($picqer);

    expect($picqer->getAccessToken())->toBe('in-flight-access-token');
});

it('does not adopt a token that is about to expire inside picqers 10s margin', function () {
    $connection = ExactConnection::factory()->create([
        'is_active' => true,
        'access_token' => 'stored-access-token',
        'refresh_token' => 'stored-refresh-token',
        'token_expires_at' => now()->addSeconds(5)->timestamp,
    ]);

    $picqer = $connection->getPicqerConnection();
    $picqer->setAccessToken('in-flight-access-token');

    // picqer treats a token as expired 10s early, so adopting this one would
    // make it refresh anyway — having pointlessly replaced what it held.
    (picqerCallback($picqer, 'refreshAccessTokenCallback'))($picqer);

    expect($picqer->getAccessToken())->toBe('in-flight-access-token');
});

it('never lets lock trouble break the api call', function () {
    $connection = ExactConnection::factory()->create(['is_active' => true]);

    // A cache store that cannot lock must degrade to the previous
    // unsynchronised behaviour, not throw mid-request.
    Cache::shouldReceive('lock')->andThrow(new RuntimeException('no lock store'));

    $picqer = $connection->getPicqerConnection();

    (picqerCallback($picqer, 'acquireAccessTokenLockCallback'))();
    (picqerCallback($picqer, 'acquireAccessTokenUnlockCallback'))();
})->throwsNoExceptions();

it('still resolves the refresh action from config, unchanged', function () {
    // Guards against the lock work accidentally re-pointing the action.
    expect(config('exactonline-laravel-api.actions.refresh_access_token'))
        ->toBe(RefreshAccessTokenAction::class);
});
