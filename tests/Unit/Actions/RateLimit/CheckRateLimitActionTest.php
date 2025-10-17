<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use Skylence\ExactonlineLaravelApi\Exceptions\RateLimitExceededException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Models\ExactRateLimit;

beforeEach(function () {
    $this->connection = ExactConnection::factory()->create();
    $this->action = new CheckRateLimitAction;
});

it('allows requests when no limits are set', function () {
    $result = $this->action->execute($this->connection);

    expect($result)
        ->toBeArray()
        ->toHaveKey('can_proceed', true)
        ->and($result['daily_limit'])->toBeNull()
        ->and($result['daily_remaining'])->toBeNull()
        ->and($result['minutely_limit'])->toBe(60) // Default
        ->and($result['minutely_remaining'])->toBe(60);
});

it('creates rate limit record if it does not exist', function () {
    expect($this->connection->rateLimit)->toBeNull();

    $this->action->execute($this->connection);

    $this->connection->refresh();
    expect($this->connection->rateLimit)->toBeInstanceOf(ExactRateLimit::class);
});

it('throws exception when daily limit is exceeded', function () {
    config(['exactonline-laravel-api.rate_limiting.throw_on_daily_limit' => true]);

    ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'daily_limit' => 5000,
        'daily_remaining' => 0,
        'daily_reset_at' => now()->addHours(12)->timestamp,
    ]);

    $this->action->execute($this->connection);
})->throws(
    RateLimitExceededException::class,
    'Daily API rate limit of 5000 requests exceeded'
);

it('throws exception when minutely limit is exceeded and configured not to wait', function () {
    config(['exactonline-laravel-api.rate_limiting.wait_on_minutely_limit' => false]);

    ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'minutely_limit' => 60,
        'minutely_remaining' => 0,
        'minutely_reset_at' => now()->addSeconds(30)->timestamp,
    ]);

    $this->action->execute($this->connection);
})->throws(
    RateLimitExceededException::class,
    'Minutely API rate limit of 60 requests exceeded'
);

it('does not throw exception for minutely limit when configured to wait', function () {
    config(['exactonline-laravel-api.rate_limiting.wait_on_minutely_limit' => true]);

    ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'minutely_limit' => 60,
        'minutely_remaining' => 0,
        'minutely_reset_at' => now()->addSeconds(30)->timestamp,
    ]);

    Log::shouldReceive('info')
        ->once()
        ->with('Minutely rate limit exceeded, will wait', Mockery::any());

    $result = $this->action->execute($this->connection);

    expect($result['can_proceed'])->toBeTrue();
});

it('uses cached rate limits when available', function () {
    $cacheKey = "exact_rate_limits:{$this->connection->id}";
    $cachedLimits = [
        'daily_limit' => 5000,
        'daily_remaining' => 2500,
        'daily_reset_at' => now()->addHours(6)->timestamp,
        'minutely_limit' => 60,
        'minutely_remaining' => 30,
        'minutely_reset_at' => now()->addSeconds(30)->timestamp,
        'updated_at' => now()->timestamp,
    ];

    Cache::shouldReceive('get')
        ->once()
        ->with($cacheKey)
        ->andReturn($cachedLimits);

    Cache::shouldReceive('put')
        ->once()
        ->with($cacheKey, Mockery::any(), 60);

    $result = $this->action->execute($this->connection);

    expect($result['daily_remaining'])->toBe(2500)
        ->and($result['minutely_remaining'])->toBe(30);
});

it('updates rate limits from response headers', function () {
    $headers = [
        'X-RateLimit-Limit' => '5000',
        'X-RateLimit-Remaining' => '4999',
        'X-RateLimit-Reset' => (string) ((now()->addHours(24)->timestamp) * 1000), // milliseconds
        'X-RateLimit-Minutely-Limit' => '60',
        'X-RateLimit-Minutely-Remaining' => '59',
        'X-RateLimit-Minutely-Reset' => (string) ((now()->addMinute()->timestamp) * 1000),
    ];

    Cache::shouldReceive('get')->once()->andReturn(null);
    Cache::shouldReceive('put')->once();

    $result = $this->action->execute($this->connection, $headers);

    expect($result['daily_limit'])->toBe(5000)
        ->and($result['daily_remaining'])->toBe(4999)
        ->and($result['minutely_limit'])->toBe(60)
        ->and($result['minutely_remaining'])->toBe(59)
        ->and($result['can_proceed'])->toBeTrue();
});

it('logs warning when approaching daily limit', function () {
    ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'daily_limit' => 5000,
        'daily_remaining' => 400, // 92% used
        'daily_reset_at' => now()->addHours(6)->timestamp,
    ]);

    Log::shouldReceive('warning')
        ->once()
        ->with('Approaching daily rate limit', Mockery::any());

    Cache::shouldReceive('get')->once()->andReturn(null);
    Cache::shouldReceive('put')->once();

    $result = $this->action->execute($this->connection);

    expect($result['can_proceed'])->toBeTrue();
});

it('logs warning when minutely limit is low', function () {
    ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'minutely_limit' => 60,
        'minutely_remaining' => 5, // Less than 10
        'minutely_reset_at' => now()->addSeconds(30)->timestamp,
    ]);

    Log::shouldReceive('warning')
        ->once()
        ->with('Low minutely rate limit', Mockery::any());

    Cache::shouldReceive('get')->once()->andReturn(null);
    Cache::shouldReceive('put')->once();

    $result = $this->action->execute($this->connection);

    expect($result['can_proceed'])->toBeTrue();
});

it('merges cached limits with database using most restrictive values', function () {
    // Database has higher remaining
    ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'daily_remaining' => 3000,
        'minutely_remaining' => 50,
    ]);

    // Cache has lower remaining (more restrictive)
    $cachedLimits = [
        'daily_remaining' => 2500,
        'minutely_remaining' => 30,
        'daily_reset_at' => now()->addHours(6)->timestamp,
        'minutely_reset_at' => now()->addSeconds(30)->timestamp,
    ];

    Cache::shouldReceive('get')
        ->once()
        ->andReturn($cachedLimits);

    Cache::shouldReceive('put')->once();

    $result = $this->action->execute($this->connection);

    // Should use the lower (cached) values
    expect($result['daily_remaining'])->toBe(2500)
        ->and($result['minutely_remaining'])->toBe(30);
});

it('handles daily limit when configured not to throw', function () {
    config(['exactonline-laravel-api.rate_limiting.throw_on_daily_limit' => false]);

    ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'daily_limit' => 5000,
        'daily_remaining' => 0,
        'daily_reset_at' => now()->addHours(12)->timestamp,
    ]);

    Log::shouldReceive('warning')
        ->once()
        ->with('Daily rate limit exceeded but configured to continue', Mockery::any());

    Cache::shouldReceive('get')->once()->andReturn(null);
    Cache::shouldReceive('put')->once();

    $result = $this->action->execute($this->connection);

    expect($result['can_proceed'])->toBeTrue();
});
