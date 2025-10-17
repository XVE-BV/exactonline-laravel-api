<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Picqer\Financials\Exact\Connection;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\AcquireAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Events\TokenAcquired;
use Skylence\ExactonlineLaravelApi\Exceptions\TokenRefreshException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

beforeEach(function () {
    Event::fake();
    
    $this->connection = ExactConnection::factory()->create([
        'access_token' => null,
        'refresh_token' => null,
        'token_expires_at' => null,
    ]);
    
    $this->action = new AcquireAccessTokenAction();
});

it('can acquire access token with valid authorization code', function () {
    // Mock the picqer connection
    $picqerConnection = $this->mock(Connection::class, function (MockInterface $mock) {
        $mock->shouldReceive('setAuthorizationCode')
            ->once()
            ->with('test-authorization-code');
        
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
    
    // Mock the connection's getPicqerConnection method
    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->once()
        ->andReturn($picqerConnection);
    
    // Execute the action
    $tokens = $this->action->execute($this->connection, 'test-authorization-code');
    
    // Assert tokens are returned
    expect($tokens)
        ->toBeArray()
        ->toHaveKeys(['access_token', 'refresh_token', 'expires_at'])
        ->and($tokens['access_token'])->toBe('new-access-token')
        ->and($tokens['refresh_token'])->toBe('new-refresh-token');
    
    // Assert connection is updated in database
    $this->connection->refresh();
    expect(decrypt($this->connection->access_token))->toBe('new-access-token')
        ->and(decrypt($this->connection->refresh_token))->toBe('new-refresh-token')
        ->and($this->connection->is_active)->toBeTrue();
    
    // Assert event is dispatched
    Event::assertDispatched(TokenAcquired::class, function ($event) {
        return $event->connection->id === $this->connection->id;
    });
});

it('throws exception for empty authorization code', function () {
    $this->action->execute($this->connection, '');
})->throws(
    TokenRefreshException::class,
    'Authorization code cannot be empty'
);

it('throws exception when token exchange fails', function () {
    $picqerConnection = $this->mock(Connection::class, function (MockInterface $mock) {
        $mock->shouldReceive('setAuthorizationCode')
            ->once()
            ->with('test-code');
        
        $mock->shouldReceive('connect')
            ->once()
            ->andThrow(new Exception('Invalid authorization code'));
    });
    
    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->once()
        ->andReturn($picqerConnection);
    
    $this->action->execute($this->connection, 'test-code');
})->throws(
    TokenRefreshException::class,
    'Failed to acquire access token'
);

it('calculates refresh token expiry correctly', function () {
    $picqerConnection = $this->mock(Connection::class, function (MockInterface $mock) {
        $mock->shouldReceive('setAuthorizationCode')->once();
        $mock->shouldReceive('connect')->once();
        $mock->shouldReceive('getAccessToken')->once()->andReturn('token');
        $mock->shouldReceive('getRefreshToken')->once()->andReturn('refresh');
        $mock->shouldReceive('getTokenExpires')->once()->andReturn(now()->addMinutes(10)->timestamp);
    });
    
    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->once()
        ->andReturn($picqerConnection);
    
    $beforeTime = now();
    $this->action->execute($this->connection, 'test-code');
    
    $this->connection->refresh();
    
    // Refresh token should expire in 30 days
    expect($this->connection->refresh_token_expires_at)
        ->toBeGreaterThanOrEqual($beforeTime->addDays(29)->timestamp)
        ->toBeLessThanOrEqual($beforeTime->addDays(31)->timestamp);
});

it('logs token acquisition', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Acquired new access token for Exact Online connection', Mockery::any());
    
    Log::shouldReceive('error')->never();
    
    $picqerConnection = $this->mock(Connection::class, function (MockInterface $mock) {
        $mock->shouldReceive('setAuthorizationCode')->once();
        $mock->shouldReceive('connect')->once();
        $mock->shouldReceive('getAccessToken')->once()->andReturn('token');
        $mock->shouldReceive('getRefreshToken')->once()->andReturn('refresh');
        $mock->shouldReceive('getTokenExpires')->once()->andReturn(now()->addMinutes(10)->timestamp);
    });
    
    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->once()
        ->andReturn($picqerConnection);
    
    $this->action->execute($this->connection, 'test-code');
});

it('marks connection as active after successful token acquisition', function () {
    expect($this->connection->is_active)->toBeFalse();
    
    $picqerConnection = $this->mock(Connection::class, function (MockInterface $mock) {
        $mock->shouldReceive('setAuthorizationCode')->once();
        $mock->shouldReceive('connect')->once();
        $mock->shouldReceive('getAccessToken')->once()->andReturn('token');
        $mock->shouldReceive('getRefreshToken')->once()->andReturn('refresh');
        $mock->shouldReceive('getTokenExpires')->once()->andReturn(now()->addMinutes(10)->timestamp);
    });
    
    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->once()
        ->andReturn($picqerConnection);
    
    $this->action->execute($this->connection, 'test-code');
    
    $this->connection->refresh();
    expect($this->connection->is_active)->toBeTrue();
});
