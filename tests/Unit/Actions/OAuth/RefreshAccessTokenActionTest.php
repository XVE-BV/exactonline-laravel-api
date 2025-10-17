<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Picqer\Financials\Exact\Connection;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Events\TokenRefreshed;
use Skylence\ExactonlineLaravelApi\Events\TokenRefreshFailed;
use Skylence\ExactonlineLaravelApi\Exceptions\TokenRefreshException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

beforeEach(function () {
    Event::fake();

    $this->connection = ExactConnection::factory()->create([
        'access_token' => encrypt('old-access-token'),
        'refresh_token' => encrypt('valid-refresh-token'),
        'token_expires_at' => now()->addMinutes(2)->timestamp, // Expires soon
    ]);

    $this->action = new RefreshAccessTokenAction;
});

it('refreshes access token successfully with distributed lock', function () {
    // Mock successful lock acquisition
    $lock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
    $lock->shouldReceive('get')->once()->andReturn(true);
    $lock->shouldReceive('release')->once();

    Cache::shouldReceive('lock')
        ->once()
        ->with("exact-token-refresh:{$this->connection->id}", 30)
        ->andReturn($lock);

    // Mock the picqer connection for refresh
    $picqerConnection = $this->mock(Connection::class, function (MockInterface $mock) {
        $mock->shouldReceive('setRefreshToken')
            ->once()
            ->with('valid-refresh-token');

        $mock->shouldReceive('connect')
            ->once()
            ->andReturn(true);

        $mock->shouldReceive('getAccessToken')
            ->once()
            ->andReturn('new-access-token');

        $mock->shouldReceive('getRefreshToken')
            ->once()
            ->andReturn('new-refresh-token');

        $mock->shouldReceive('getTokenExpires')
            ->once()
            ->andReturn(now()->addMinutes(10)->timestamp);
    });

    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->once()
        ->andReturn($picqerConnection);

    // Execute the action
    $tokens = $this->action->execute($this->connection);

    // Assert tokens are returned
    expect($tokens)
        ->toBeArray()
        ->toHaveKeys(['access_token', 'refresh_token', 'expires_at'])
        ->and($tokens['access_token'])->toBe('new-access-token')
        ->and($tokens['refresh_token'])->toBe('new-refresh-token');

    // Assert event is dispatched
    Event::assertDispatched(TokenRefreshed::class, function ($event) {
        return $event->connection->id === $this->connection->id;
    });
});

it('waits for lock when another process is refreshing', function () {
    // Mock failed lock acquisition (another process has it)
    $lock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
    $lock->shouldReceive('get')->once()->andReturn(false);
    $lock->shouldReceive('release')->never();

    Cache::shouldReceive('lock')
        ->once()
        ->with("exact-token-refresh:{$this->connection->id}", 30)
        ->andReturn($lock);

    // Simulate the connection being refreshed by another process
    $this->connection->update([
        'access_token' => encrypt('token-from-other-process'),
        'refresh_token' => encrypt('refresh-from-other-process'),
        'token_expires_at' => now()->addMinutes(10)->timestamp,
    ]);

    // Execute should wait and return the updated tokens
    $tokens = $this->action->execute($this->connection);

    expect($tokens)
        ->toBeArray()
        ->and($tokens['access_token'])->toBe('token-from-other-process')
        ->and($tokens['refresh_token'])->toBe('refresh-from-other-process');
});

it('checks if token needs refresh with 9-minute threshold', function () {
    // Token expires in 8 minutes - should need refresh
    $connection1 = ExactConnection::factory()->create([
        'token_expires_at' => now()->addMinutes(8)->timestamp,
    ]);
    expect($this->invokeMethod($this->action, 'tokenNeedsRefresh', [$connection1]))
        ->toBeTrue();

    // Token expires in 11 minutes - should not need refresh
    $connection2 = ExactConnection::factory()->create([
        'token_expires_at' => now()->addMinutes(11)->timestamp,
    ]);
    expect($this->invokeMethod($this->action, 'tokenNeedsRefresh', [$connection2]))
        ->toBeFalse();

    // Token already expired - should need refresh
    $connection3 = ExactConnection::factory()->create([
        'token_expires_at' => now()->subMinutes(1)->timestamp,
    ]);
    expect($this->invokeMethod($this->action, 'tokenNeedsRefresh', [$connection3]))
        ->toBeTrue();

    // No expiry set - should need refresh
    $connection4 = ExactConnection::factory()->create([
        'token_expires_at' => null,
    ]);
    expect($this->invokeMethod($this->action, 'tokenNeedsRefresh', [$connection4]))
        ->toBeTrue();
});

it('implements exponential backoff retry on failure', function () {
    $lock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
    $lock->shouldReceive('get')->once()->andReturn(true);
    $lock->shouldReceive('release')->once();

    Cache::shouldReceive('lock')
        ->once()
        ->andReturn($lock);

    $attemptCount = 0;
    $picqerConnection = $this->mock(Connection::class, function (MockInterface $mock) use (&$attemptCount) {
        $mock->shouldReceive('setRefreshToken')->times(3);

        // Fail first 2 attempts, succeed on 3rd
        $mock->shouldReceive('connect')
            ->times(3)
            ->andReturnUsing(function () use (&$attemptCount) {
                $attemptCount++;
                if ($attemptCount < 3) {
                    throw new Exception('Network error');
                }

                return true;
            });

        $mock->shouldReceive('getAccessToken')
            ->once()
            ->andReturn('new-token');

        $mock->shouldReceive('getRefreshToken')
            ->once()
            ->andReturn('new-refresh');

        $mock->shouldReceive('getTokenExpires')
            ->once()
            ->andReturn(now()->addMinutes(10)->timestamp);
    });

    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->times(3)
        ->andReturn($picqerConnection);

    // Track sleep calls to verify exponential backoff
    $sleepCalls = [];
    $this->action = Mockery::mock(RefreshAccessTokenAction::class)->makePartial();
    $this->action->shouldReceive('sleep')
        ->times(2)
        ->andReturnUsing(function ($microseconds) use (&$sleepCalls) {
            $sleepCalls[] = $microseconds;
        });

    $tokens = $this->action->execute($this->connection);

    expect($tokens['access_token'])->toBe('new-token')
        ->and($attemptCount)->toBe(3)
        ->and($sleepCalls)->toBe([100000, 200000]); // 100ms, 200ms
});

it('throws exception after max retries exceeded', function () {
    $lock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
    $lock->shouldReceive('get')->once()->andReturn(true);
    $lock->shouldReceive('release')->once();

    Cache::shouldReceive('lock')
        ->once()
        ->andReturn($lock);

    $picqerConnection = $this->mock(Connection::class, function (MockInterface $mock) {
        $mock->shouldReceive('setRefreshToken')->times(3);
        $mock->shouldReceive('connect')
            ->times(3)
            ->andThrow(new Exception('Persistent network error'));
    });

    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->times(3)
        ->andReturn($picqerConnection);

    // Mock sleep to speed up test
    $this->action = Mockery::mock(RefreshAccessTokenAction::class)->makePartial();
    $this->action->shouldReceive('sleep')->times(2);

    Event::fake();

    $this->action->execute($this->connection);
})->throws(
    TokenRefreshException::class,
    'Token refresh failed after all retries'
);

it('dispatches TokenRefreshFailed event on failure', function () {
    $lock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
    $lock->shouldReceive('get')->once()->andReturn(true);
    $lock->shouldReceive('release')->once();

    Cache::shouldReceive('lock')
        ->once()
        ->andReturn($lock);

    $picqerConnection = $this->mock(Connection::class, function (MockInterface $mock) {
        $mock->shouldReceive('setRefreshToken')->times(3);
        $mock->shouldReceive('connect')
            ->times(3)
            ->andThrow(new Exception('API error'));
    });

    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->times(3)
        ->andReturn($picqerConnection);

    $this->action = Mockery::mock(RefreshAccessTokenAction::class)->makePartial();
    $this->action->shouldReceive('sleep')->times(2);

    try {
        $this->action->execute($this->connection);
    } catch (TokenRefreshException $e) {
        // Expected
    }

    Event::assertDispatched(TokenRefreshFailed::class, function ($event) {
        return $event->connection->id === $this->connection->id
            && $event->exception instanceof TokenRefreshException;
    });
});

it('always releases lock even on exception', function () {
    $lock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
    $lock->shouldReceive('get')->once()->andReturn(true);
    $lock->shouldReceive('release')->once(); // Must be called

    Cache::shouldReceive('lock')
        ->once()
        ->andReturn($lock);

    $picqerConnection = $this->mock(Connection::class, function (MockInterface $mock) {
        $mock->shouldReceive('setRefreshToken')->once();
        $mock->shouldReceive('connect')
            ->once()
            ->andThrow(new Exception('Critical error'));
    });

    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->once()
        ->andReturn($picqerConnection);

    try {
        $this->action->execute($this->connection);
    } catch (Exception $e) {
        // Lock should still be released
    }

    // Test will fail if release() wasn't called due to Mockery expectations
});

it('skips refresh if token was already refreshed after lock acquisition', function () {
    $lock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
    $lock->shouldReceive('get')->once()->andReturn(true);
    $lock->shouldReceive('release')->once();

    Cache::shouldReceive('lock')
        ->once()
        ->andReturn($lock);

    // Update token to not need refresh (simulating another process refreshed it)
    $this->connection->update([
        'token_expires_at' => now()->addMinutes(10)->timestamp,
        'access_token' => encrypt('recently-refreshed-token'),
    ]);

    // Should not call picqer connection at all
    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')->never();

    $tokens = $this->action->execute($this->connection);

    expect($tokens['access_token'])->toBe('recently-refreshed-token');
});

it('logs all refresh attempts with context', function () {
    Log::shouldReceive('info')
        ->with(Mockery::pattern('/Refreshing access token/'), Mockery::any())
        ->once();

    Log::shouldReceive('warning')
        ->with(Mockery::pattern('/Token refresh retry/'), Mockery::any())
        ->never(); // Should succeed on first try

    $lock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
    $lock->shouldReceive('get')->once()->andReturn(true);
    $lock->shouldReceive('release')->once();

    Cache::shouldReceive('lock')->once()->andReturn($lock);

    $picqerConnection = $this->mock(Connection::class, function (MockInterface $mock) {
        $mock->shouldReceive('setRefreshToken')->once();
        $mock->shouldReceive('connect')->once();
        $mock->shouldReceive('getAccessToken')->once()->andReturn('new-token');
        $mock->shouldReceive('getRefreshToken')->once()->andReturn('new-refresh');
        $mock->shouldReceive('getTokenExpires')->once()->andReturn(now()->addMinutes(10)->timestamp);
    });

    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->once()
        ->andReturn($picqerConnection);

    $this->action->execute($this->connection);
});

it('handles timeout while waiting for another process to refresh', function () {
    $lock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
    $lock->shouldReceive('get')->once()->andReturn(false);

    Cache::shouldReceive('lock')
        ->once()
        ->andReturn($lock);

    // Connection never gets refreshed by other process
    // Token stays expired

    $this->action = Mockery::mock(RefreshAccessTokenAction::class)->makePartial();
    $this->action->shouldReceive('sleep')
        ->times(30) // Max wait iterations
        ->with(100000);

    $this->action->execute($this->connection);
})->throws(
    TokenRefreshException::class,
    'Timeout waiting for token refresh by another process'
);

// Helper method to invoke protected/private methods
function invokeMethod(&$object, $methodName, array $parameters = [])
{
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $parameters);
}
