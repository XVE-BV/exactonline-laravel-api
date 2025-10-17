<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Picqer\Financials\Exact\Connection;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use Skylence\ExactonlineLaravelApi\Events\RateLimitApproaching;
use Skylence\ExactonlineLaravelApi\Events\RateLimitUpdated;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Models\ExactRateLimit;

beforeEach(function () {
    Event::fake();
    $this->connection = ExactConnection::factory()->create();
    $this->action = new TrackRateLimitUsageAction();
});

it('tracks rate limit usage from picqer connection', function () {
    $picqerConnection = Mockery::mock(Connection::class);
    
    // Mock the rate limit methods
    $picqerConnection->shouldReceive('getDailyLimit')->once()->andReturn(5000);
    $picqerConnection->shouldReceive('getDailyLimitRemaining')->once()->andReturn(4500);
    $picqerConnection->shouldReceive('getDailyLimitReset')->once()->andReturn(now()->addHours(12)->timestamp * 1000);
    $picqerConnection->shouldReceive('getMinutelyLimit')->once()->andReturn(60);
    $picqerConnection->shouldReceive('getMinutelyLimitRemaining')->once()->andReturn(55);
    $picqerConnection->shouldReceive('getMinutelyLimitReset')->once()->andReturn(now()->addSeconds(30)->timestamp * 1000);
    
    Cache::shouldReceive('put')->once();
    
    $result = $this->action->execute($this->connection, $picqerConnection);
    
    expect($result)
        ->toHaveKey('tracked', true)
        ->and($result['daily_usage'])->toBe(10.0) // (5000 - 4500) / 5000 * 100
        ->and($result['minutely_usage'])->toBeGreaterThan(8.0)->toBeLessThan(9.0) // (60 - 55) / 60 * 100
        ->and($result['warnings'])->toBeEmpty();
    
    // Check that rate limit was stored
    $rateLimit = $this->connection->rateLimit;
    expect($rateLimit)->toBeInstanceOf(ExactRateLimit::class)
        ->and($rateLimit->daily_limit)->toBe(5000)
        ->and($rateLimit->daily_remaining)->toBe(4500)
        ->and($rateLimit->minutely_limit)->toBe(60)
        ->and($rateLimit->minutely_remaining)->toBe(55);
});

it('returns empty result when no rate limit headers are present', function () {
    $picqerConnection = Mockery::mock(Connection::class);
    
    // No rate limit methods exist
    $picqerConnection->shouldReceive('getDailyLimit')->never();
    
    $result = $this->action->execute($this->connection, $picqerConnection);
    
    expect($result)
        ->toHaveKey('tracked', false)
        ->and($result['daily_usage'])->toBeNull()
        ->and($result['minutely_usage'])->toBeNull()
        ->and($result['warnings'])->toBeEmpty();
});

it('generates warnings when approaching daily limit', function () {
    $picqerConnection = Mockery::mock(Connection::class);
    
    // 95% used (4750 of 5000)
    $picqerConnection->shouldReceive('getDailyLimit')->once()->andReturn(5000);
    $picqerConnection->shouldReceive('getDailyLimitRemaining')->once()->andReturn(250);
    $picqerConnection->shouldReceive('getDailyLimitReset')->once()->andReturn(now()->addHours(6)->timestamp * 1000);
    $picqerConnection->shouldReceive('getMinutelyLimit')->once()->andReturn(60);
    $picqerConnection->shouldReceive('getMinutelyLimitRemaining')->once()->andReturn(60);
    $picqerConnection->shouldReceive('getMinutelyLimitReset')->once()->andReturn(now()->addMinute()->timestamp * 1000);
    
    Cache::shouldReceive('put')->once();
    
    Log::shouldReceive('debug')->twice();
    Log::shouldReceive('warning')
        ->once()
        ->with('Rate limit warning', Mockery::on(function ($context) {
            return str_contains($context['warning'], 'Daily rate limit is at 95.0%');
        }));
    
    $result = $this->action->execute($this->connection, $picqerConnection);
    
    expect($result['warnings'])
        ->toHaveCount(1)
        ->and($result['warnings'][0])->toContain('Daily rate limit is at 95.0%');
});

it('generates warnings when approaching minutely limit', function () {
    $picqerConnection = Mockery::mock(Connection::class);
    
    // 85% used (51 of 60)
    $picqerConnection->shouldReceive('getDailyLimit')->once()->andReturn(5000);
    $picqerConnection->shouldReceive('getDailyLimitRemaining')->once()->andReturn(5000);
    $picqerConnection->shouldReceive('getDailyLimitReset')->once()->andReturn(now()->addDay()->timestamp * 1000);
    $picqerConnection->shouldReceive('getMinutelyLimit')->once()->andReturn(60);
    $picqerConnection->shouldReceive('getMinutelyLimitRemaining')->once()->andReturn(9);
    $picqerConnection->shouldReceive('getMinutelyLimitReset')->once()->andReturn(now()->addSeconds(30)->timestamp * 1000);
    
    Cache::shouldReceive('put')->once();
    
    $result = $this->action->execute($this->connection, $picqerConnection);
    
    expect($result['warnings'])
        ->toHaveCount(2) // Minutely at 85% and only 9 remaining
        ->and($result['warnings'][0])->toContain('Minutely rate limit is at 85.0%')
        ->and($result['warnings'][1])->toContain('Only 9 minutely requests remaining');
});

it('dispatches RateLimitUpdated event', function () {
    $picqerConnection = Mockery::mock(Connection::class);
    
    $picqerConnection->shouldReceive('getDailyLimit')->once()->andReturn(5000);
    $picqerConnection->shouldReceive('getDailyLimitRemaining')->once()->andReturn(4500);
    $picqerConnection->shouldReceive('getDailyLimitReset')->once()->andReturn(now()->addHours(12)->timestamp * 1000);
    $picqerConnection->shouldReceive('getMinutelyLimit')->once()->andReturn(60);
    $picqerConnection->shouldReceive('getMinutelyLimitRemaining')->once()->andReturn(55);
    $picqerConnection->shouldReceive('getMinutelyLimitReset')->once()->andReturn(now()->addSeconds(30)->timestamp * 1000);
    
    Cache::shouldReceive('put')->once();
    
    $this->action->execute($this->connection, $picqerConnection);
    
    Event::assertDispatched(RateLimitUpdated::class, function ($event) {
        return $event->connection->id === $this->connection->id;
    });
});

it('dispatches RateLimitApproaching event when warnings exist', function () {
    $picqerConnection = Mockery::mock(Connection::class);
    
    // Create a scenario with warnings
    $picqerConnection->shouldReceive('getDailyLimit')->once()->andReturn(5000);
    $picqerConnection->shouldReceive('getDailyLimitRemaining')->once()->andReturn(50); // Very low
    $picqerConnection->shouldReceive('getDailyLimitReset')->once()->andReturn(now()->addHours(3)->timestamp * 1000);
    $picqerConnection->shouldReceive('getMinutelyLimit')->once()->andReturn(60);
    $picqerConnection->shouldReceive('getMinutelyLimitRemaining')->once()->andReturn(5); // Very low
    $picqerConnection->shouldReceive('getMinutelyLimitReset')->once()->andReturn(now()->addSeconds(30)->timestamp * 1000);
    
    Cache::shouldReceive('put')->once();
    
    Log::shouldReceive('debug')->twice();
    Log::shouldReceive('warning')->times(4); // Multiple warnings
    
    $this->action->execute($this->connection, $picqerConnection);
    
    Event::assertDispatched(RateLimitApproaching::class, function ($event) {
        return $event->connection->id === $this->connection->id
            && ! empty($event->warnings);
    });
});

it('caches rate limits for quick access', function () {
    $picqerConnection = Mockery::mock(Connection::class);
    
    $picqerConnection->shouldReceive('getDailyLimit')->once()->andReturn(5000);
    $picqerConnection->shouldReceive('getDailyLimitRemaining')->once()->andReturn(4500);
    $picqerConnection->shouldReceive('getDailyLimitReset')->once()->andReturn(now()->addHours(12)->timestamp * 1000);
    $picqerConnection->shouldReceive('getMinutelyLimit')->once()->andReturn(60);
    $picqerConnection->shouldReceive('getMinutelyLimitRemaining')->once()->andReturn(55);
    $picqerConnection->shouldReceive('getMinutelyLimitReset')->once()->andReturn(now()->addSeconds(30)->timestamp * 1000);
    
    $cacheKey = "exact_rate_limits:{$this->connection->id}";
    
    Cache::shouldReceive('put')
        ->once()
        ->with($cacheKey, Mockery::on(function ($data) {
            return $data['daily_limit'] === 5000
                && $data['daily_remaining'] === 4500
                && $data['minutely_limit'] === 60
                && $data['minutely_remaining'] === 55
                && isset($data['updated_at']);
        }), 60);
    
    $this->action->execute($this->connection, $picqerConnection);
});

it('calculates usage percentage correctly', function () {
    $picqerConnection = Mockery::mock(Connection::class);
    
    // Test various usage scenarios
    $picqerConnection->shouldReceive('getDailyLimit')->once()->andReturn(1000);
    $picqerConnection->shouldReceive('getDailyLimitRemaining')->once()->andReturn(750); // 25% used
    $picqerConnection->shouldReceive('getDailyLimitReset')->once()->andReturn(now()->addHours(12)->timestamp * 1000);
    $picqerConnection->shouldReceive('getMinutelyLimit')->once()->andReturn(60);
    $picqerConnection->shouldReceive('getMinutelyLimitRemaining')->once()->andReturn(30); // 50% used
    $picqerConnection->shouldReceive('getMinutelyLimitReset')->once()->andReturn(now()->addSeconds(30)->timestamp * 1000);
    
    Cache::shouldReceive('put')->once();
    
    $result = $this->action->execute($this->connection, $picqerConnection);
    
    expect($result['daily_usage'])->toBe(25.0)
        ->and($result['minutely_usage'])->toBe(50.0);
});

it('updates existing rate limit record', function () {
    // Create existing rate limit
    $rateLimit = ExactRateLimit::factory()->create([
        'connection_id' => $this->connection->id,
        'daily_limit' => 1000,
        'daily_remaining' => 500,
    ]);
    
    $picqerConnection = Mockery::mock(Connection::class);
    
    $picqerConnection->shouldReceive('getDailyLimit')->once()->andReturn(5000);
    $picqerConnection->shouldReceive('getDailyLimitRemaining')->once()->andReturn(4000);
    $picqerConnection->shouldReceive('getDailyLimitReset')->once()->andReturn(now()->addHours(12)->timestamp * 1000);
    $picqerConnection->shouldReceive('getMinutelyLimit')->once()->andReturn(60);
    $picqerConnection->shouldReceive('getMinutelyLimitRemaining')->once()->andReturn(50);
    $picqerConnection->shouldReceive('getMinutelyLimitReset')->once()->andReturn(now()->addSeconds(30)->timestamp * 1000);
    
    Cache::shouldReceive('put')->once();
    
    $this->action->execute($this->connection, $picqerConnection);
    
    $rateLimit->refresh();
    
    expect($rateLimit->daily_limit)->toBe(5000)
        ->and($rateLimit->daily_remaining)->toBe(4000)
        ->and($rateLimit->minutely_limit)->toBe(60)
        ->and($rateLimit->minutely_remaining)->toBe(50);
});
