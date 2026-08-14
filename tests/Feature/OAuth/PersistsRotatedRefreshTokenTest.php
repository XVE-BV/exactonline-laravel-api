<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use XVE\ExactonlineLaravelApi\Actions\OAuth\StoreTokensAction;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

/**
 * Exact rotates its single-use refresh token on every refresh and invalidates
 * the old one immediately. picqer does that refresh itself inside
 * Connection::acquireAccessToken() and surfaces the new pair ONLY through
 * setTokenUpdateCallback(). Without that callback the rotated token is
 * discarded at the end of the request while Exact has already killed the
 * stored copy — every later refresh then fails and the connection is silently
 * dead until the stored token reaches its ~30-day idle expiry.
 *
 * Measured cost of not having this: a live connection died on 18 Jul 2026 and
 * every Exact call 400'd for twelve days until someone re-authorised by hand.
 */
it('registers a token update callback on the picqer connection', function () {
    $connection = ExactConnection::factory()->create([
        'is_active' => true,
        'access_token' => 'access-token',
        'refresh_token' => 'refresh-token',
        'token_expires_at' => now()->addMinutes(10)->timestamp,
    ]);

    $picqer = $connection->getPicqerConnection();

    // The property is private on picqer's Connection, so assert through
    // reflection rather than by triggering a real OAuth round-trip.
    $property = (new ReflectionClass($picqer))->getProperty('tokenUpdateCallback');
    $property->setAccessible(true);

    expect($property->getValue($picqer))->toBeCallable();
});

it('persists tokens that picqer rotated on its own initiative', function () {
    $connection = ExactConnection::factory()->create([
        'is_active' => true,
        'access_token' => 'old-access-token',
        'refresh_token' => 'old-refresh-token',
        'token_expires_at' => now()->subMinute()->timestamp,
    ]);

    $picqer = $connection->getPicqerConnection();

    // Simulate what picqer does after it refreshes: it sets the new pair on
    // itself and then invokes the callback.
    $picqer->setAccessToken('rotated-access-token');
    $picqer->setRefreshToken('rotated-refresh-token');
    $picqer->setTokenExpires(now()->addMinutes(10)->timestamp);

    $property = (new ReflectionClass($picqer))->getProperty('tokenUpdateCallback');
    $property->setAccessible(true);
    ($property->getValue($picqer))($picqer);

    $connection->refresh();

    expect($connection->getDecryptedAccessToken())->toBe('rotated-access-token')
        ->and($connection->getDecryptedRefreshToken())->toBe('rotated-refresh-token')
        // The rotation resets the ~30-day idle window; without this the
        // connection would still expire on the original schedule.
        ->and($connection->refresh_token_expires_at)->toBeGreaterThan(now()->addDays(29)->timestamp);
});

it('never lets a failed persist break the in-flight api call', function () {
    $connection = ExactConnection::factory()->create([
        'is_active' => true,
        'access_token' => 'old-access-token',
        'refresh_token' => 'old-refresh-token',
    ]);

    // The callback fires from inside a live Exact request; a storage failure
    // must be loud in the log but must not turn a successful call into an
    // exception.
    $this->mock(StoreTokensAction::class)
        ->shouldReceive('execute')
        ->andThrow(new RuntimeException('database is on fire'));

    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn (string $message): bool => str_contains($message, 'Failed to persist refresh token'));

    $picqer = $connection->getPicqerConnection();
    $picqer->setAccessToken('rotated-access-token');
    $picqer->setRefreshToken('rotated-refresh-token');

    $property = (new ReflectionClass($picqer))->getProperty('tokenUpdateCallback');
    $property->setAccessible(true);

    ($property->getValue($picqer))($picqer);
    // Reaching this point without an exception IS the assertion; the Log
    // expectation above proves the failure was reported rather than swallowed.
});

it('ignores a callback that carries no usable token pair', function () {
    $connection = ExactConnection::factory()->create([
        'is_active' => true,
        'access_token' => 'old-access-token',
        'refresh_token' => 'old-refresh-token',
    ]);

    $this->mock(StoreTokensAction::class)->shouldNotReceive('execute');

    $picqer = $connection->getPicqerConnection();
    $picqer->setAccessToken('');
    $picqer->setRefreshToken('');

    $property = (new ReflectionClass($picqer))->getProperty('tokenUpdateCallback');
    $property->setAccessible(true);

    ($property->getValue($picqer))($picqer);

    $connection->refresh();

    expect($connection->getDecryptedAccessToken())->toBe('old-access-token');
});
