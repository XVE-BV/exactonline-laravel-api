<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\WaitForRateLimitResetAction;
use Skylence\ExactonlineLaravelApi\Exceptions\RateLimitExceededException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Models\ExactRateLimit;

beforeEach(function () {
    $this->connection = ExactConnection::factory()->create();
    $this->action = new WaitForRateLimitResetAction;
});

it('waits for minutely rate limit to reset', function () {
    $rateLimit = ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'minutely_limit' => 60,
        'minutely_remaining' => 0,
        'minutely_reset_at' => now()->addSeconds(2)->timestamp, // 2 seconds from now
    ]);

    Log::shouldReceive('info')
        ->once()
        ->with('Waiting for rate limit to reset', Mockery::any());

    Log::shouldReceive('info')
        ->once()
        ->with('Rate limit wait completed', Mockery::any());

    $startTime = microtime(true);
    $this->action->execute($rateLimit, 'minutely');
    $elapsedTime = microtime(true) - $startTime;

    // Should have waited at least 2 seconds (plus 1 second buffer)
    expect($elapsedTime)->toBeGreaterThanOrEqual(2.0);

    // Rate limit should be reset
    $rateLimit->refresh();
    expect($rateLimit->minutely_remaining)->toBe(60);
});

it('throws exception when minutely wait time exceeds maximum', function () {
    config(['exactonline-laravel-api.rate_limiting.max_wait_seconds' => 30]);

    $rateLimit = ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'minutely_limit' => 60,
        'minutely_remaining' => 0,
        'minutely_reset_at' => now()->addSeconds(120)->timestamp, // 120 seconds from now
    ]);

    Log::shouldReceive('error')
        ->once()
        ->with('Minutely rate limit reset time exceeds maximum wait time', Mockery::any());

    $this->action->execute($rateLimit, 'minutely');
})->throws(
    RateLimitExceededException::class,
    'Minutely API rate limit of 60 requests exceeded'
);

it('throws exception for daily rate limit by default', function () {
    $rateLimit = ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'daily_limit' => 5000,
        'daily_remaining' => 0,
        'daily_reset_at' => now()->addHours(12)->timestamp, // 12 hours from now
    ]);

    Log::shouldReceive('error')
        ->once()
        ->with('Daily rate limit reset time exceeds maximum wait time', Mockery::any());

    $this->action->execute($rateLimit, 'daily');
})->throws(
    RateLimitExceededException::class,
    'Daily API rate limit of 5000 requests exceeded'
);

it('waits for daily rate limit if reset is within max wait time', function () {
    config(['exactonline-laravel-api.rate_limiting.max_wait_seconds' => 10]);

    $rateLimit = ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'daily_limit' => 5000,
        'daily_remaining' => 0,
        'daily_reset_at' => now()->addSeconds(2)->timestamp, // 2 seconds from now
    ]);

    Log::shouldReceive('info')
        ->once()
        ->with('Waiting for rate limit to reset', Mockery::any());

    Log::shouldReceive('info')
        ->once()
        ->with('Rate limit wait completed', Mockery::any());

    $startTime = microtime(true);
    $this->action->execute($rateLimit, 'daily');
    $elapsedTime = microtime(true) - $startTime;

    // Should have waited at least 2 seconds
    expect($elapsedTime)->toBeGreaterThanOrEqual(2.0);

    // Daily limit should be reset
    $rateLimit->refresh();
    expect($rateLimit->daily_remaining)->toBe($rateLimit->daily_limit);
});

it('sleeps in chunks for longer waits', function () {
    $rateLimit = ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'minutely_limit' => 60,
        'minutely_remaining' => 0,
        'minutely_reset_at' => now()->addSeconds(15)->timestamp, // 15 seconds from now
    ]);

    Log::shouldReceive('info')->twice();

    // Should log debug messages for chunks (15 seconds = 2 chunks)
    Log::shouldReceive('debug')
        ->with('Rate limit wait progress', Mockery::on(function ($context) {
            return $context['chunk'] === 1 && $context['total_chunks'] === 2;
        }))
        ->once();

    Log::shouldReceive('debug')
        ->with('Rate limit wait progress', Mockery::on(function ($context) {
            return $context['chunk'] === 2 && $context['total_chunks'] === 2;
        }))
        ->once();

    // Mock sleep to speed up test
    $this->action = Mockery::mock(WaitForRateLimitResetAction::class)->makePartial();
    $this->action->shouldAllowMockingProtectedMethods();

    $totalSlept = 0;
    $this->action->shouldReceive('sleep')
        ->andReturnUsing(function ($seconds) use (&$totalSlept) {
            $totalSlept += $seconds;

            return true;
        });

    $this->action->execute($rateLimit, 'minutely');

    // Should have slept for 16 seconds (15 + 1 buffer)
    expect($totalSlept)->toBe(16);
});

it('adds a buffer to ensure rate limit has reset', function () {
    $rateLimit = ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'minutely_limit' => 60,
        'minutely_remaining' => 0,
        'minutely_reset_at' => now()->addSeconds(5)->timestamp,
    ]);

    Log::shouldReceive('info')->twice();

    // Mock sleep to check exact time
    $this->action = Mockery::mock(WaitForRateLimitResetAction::class)->makePartial();
    $this->action->shouldAllowMockingProtectedMethods();

    $sleptFor = 0;
    $this->action->shouldReceive('sleep')
        ->once()
        ->andReturnUsing(function ($seconds) use (&$sleptFor) {
            $sleptFor = $seconds;

            return true;
        });

    $this->action->execute($rateLimit, 'minutely');

    // Should sleep for 6 seconds (5 + 1 buffer)
    expect($sleptFor)->toBe(6);
});

it('resets minutely counter after waiting', function () {
    $rateLimit = ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'minutely_limit' => 60,
        'minutely_remaining' => 0,
        'minutely_reset_at' => now()->addSeconds(1)->timestamp,
    ]);

    Log::shouldReceive('info')->twice();

    // Mock sleep to speed up test
    $this->action = Mockery::mock(WaitForRateLimitResetAction::class)->makePartial();
    $this->action->shouldAllowMockingProtectedMethods();
    $this->action->shouldReceive('sleep')->once();

    $this->action->execute($rateLimit, 'minutely');

    $rateLimit->refresh();

    expect($rateLimit->minutely_remaining)->toBe(60)
        ->and($rateLimit->minutely_reset_at)
        ->toBeGreaterThan(now()->timestamp)
        ->toBeLessThanOrEqual(now()->addMinute()->timestamp);
});

it('resets daily counter after waiting', function () {
    config(['exactonline-laravel-api.rate_limiting.max_wait_seconds' => 10]);

    $rateLimit = ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'daily_limit' => 5000,
        'daily_remaining' => 0,
        'daily_reset_at' => now()->addSeconds(1)->timestamp,
    ]);

    Log::shouldReceive('info')->twice();

    // Mock sleep to speed up test
    $this->action = Mockery::mock(WaitForRateLimitResetAction::class)->makePartial();
    $this->action->shouldAllowMockingProtectedMethods();
    $this->action->shouldReceive('sleep')->once();

    $this->action->execute($rateLimit, 'daily');

    $rateLimit->refresh();

    expect($rateLimit->daily_remaining)->toBe(5000)
        ->and($rateLimit->daily_reset_at)
        ->toBeGreaterThan(now()->timestamp)
        ->toBeLessThanOrEqual(now()->addDay()->timestamp);
});

// Helper to mock protected sleep method
function mockSleep($action, &$totalSlept)
{
    $action->shouldReceive('sleep')
        ->andReturnUsing(function ($seconds) use (&$totalSlept) {
            $totalSlept += $seconds;

            return true;
        });
}
