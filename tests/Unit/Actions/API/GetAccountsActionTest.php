<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Picqer\Financials\Exact\Account;
use Picqer\Financials\Exact\Connection;
use Skylence\ExactonlineLaravelApi\Actions\API\GetAccountsAction;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

beforeEach(function () {
    $this->connection = ExactConnection::factory()->create([
        'access_token' => encrypt('valid-token'),
        'refresh_token' => encrypt('valid-refresh'),
        'token_expires_at' => now()->addMinutes(10)->timestamp,
        'is_active' => true,
    ]);

    $this->action = new GetAccountsAction;
});

it('retrieves accounts successfully', function () {
    // Mock rate limit checking
    $checkRateLimitAction = Mockery::mock(CheckRateLimitAction::class);
    $checkRateLimitAction->shouldReceive('execute')
        ->once()
        ->with($this->connection);

    Config::shouldReceive('getAction')
        ->with('check_rate_limit', CheckRateLimitAction::class)
        ->once()
        ->andReturn($checkRateLimitAction);

    // Mock rate limit tracking
    $trackRateLimitAction = Mockery::mock(TrackRateLimitUsageAction::class);
    $trackRateLimitAction->shouldReceive('execute')
        ->once();

    Config::shouldReceive('getAction')
        ->with('track_rate_limit_usage', TrackRateLimitUsageAction::class)
        ->once()
        ->andReturn($trackRateLimitAction);

    // Mock picqer Account entity
    $mockAccounts = [
        (object) [
            'ID' => 'account-1',
            'Name' => 'Test Account 1',
            'Email' => 'test1@example.com',
            'attributes' => function () {
                return [
                    'ID' => 'account-1',
                    'Name' => 'Test Account 1',
                    'Email' => 'test1@example.com',
                ];
            },
        ],
        (object) [
            'ID' => 'account-2',
            'Name' => 'Test Account 2',
            'Email' => 'test2@example.com',
            'attributes' => function () {
                return [
                    'ID' => 'account-2',
                    'Name' => 'Test Account 2',
                    'Email' => 'test2@example.com',
                ];
            },
        ],
    ];

    $accountEntity = Mockery::mock('overload:'.Account::class);
    $accountEntity->shouldReceive('get')
        ->once()
        ->andReturn($mockAccounts);

    $picqerConnection = Mockery::mock(Connection::class);

    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->once()
        ->andReturn($picqerConnection);

    // Execute the action
    $accounts = $this->action->execute($this->connection);

    // Assert results
    expect($accounts)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(2)
        ->and($accounts->first())
        ->toHaveKeys(['ID', 'Name', 'Email'])
        ->and($accounts->first()['Name'])->toBe('Test Account 1');
});

it('applies OData query options correctly', function () {
    // Setup mocks
    $checkRateLimitAction = Mockery::mock(CheckRateLimitAction::class);
    $checkRateLimitAction->shouldReceive('execute')->once();

    Config::shouldReceive('getAction')
        ->with('check_rate_limit', CheckRateLimitAction::class)
        ->once()
        ->andReturn($checkRateLimitAction);

    $trackRateLimitAction = Mockery::mock(TrackRateLimitUsageAction::class);
    $trackRateLimitAction->shouldReceive('execute')->once();

    Config::shouldReceive('getAction')
        ->with('track_rate_limit_usage', TrackRateLimitUsageAction::class)
        ->once()
        ->andReturn($trackRateLimitAction);

    // Mock Account entity with query options
    $accountEntity = Mockery::mock('overload:'.Account::class);
    $accountEntity->shouldReceive('filter')
        ->once()
        ->with("Name eq 'Test'");
    $accountEntity->shouldReceive('select')
        ->once()
        ->with(['ID', 'Name']);
    $accountEntity->shouldReceive('expand')
        ->once()
        ->with('Contacts,Addresses');
    $accountEntity->shouldReceive('orderBy')
        ->once()
        ->with('Name desc');
    $accountEntity->shouldReceive('top')
        ->once()
        ->with(50);
    $accountEntity->shouldReceive('skip')
        ->once()
        ->with(100);
    $accountEntity->shouldReceive('get')
        ->once()
        ->andReturn([]);

    $picqerConnection = Mockery::mock(Connection::class);

    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->once()
        ->andReturn($picqerConnection);

    // Execute with options
    $options = [
        'filter' => "Name eq 'Test'",
        'select' => ['ID', 'Name'],
        'expand' => ['Contacts', 'Addresses'],
        'orderby' => 'Name desc',
        'top' => 50,
        'skip' => 100,
    ];

    $accounts = $this->action->execute($this->connection, $options);

    expect($accounts)->toBeInstanceOf(Collection::class);
});

it('refreshes token automatically when needed', function () {
    // Set token to expire soon (less than 9 minutes)
    $this->connection->update([
        'token_expires_at' => now()->addMinutes(5)->timestamp,
    ]);

    // Mock token refresh
    $refreshAction = Mockery::mock(RefreshAccessTokenAction::class);
    $refreshAction->shouldReceive('execute')
        ->once()
        ->with($this->connection)
        ->andReturn([
            'access_token' => 'new-token',
            'refresh_token' => 'new-refresh',
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

    Config::shouldReceive('getAction')
        ->with('refresh_access_token', RefreshAccessTokenAction::class)
        ->once()
        ->andReturn($refreshAction);

    // Mock other dependencies
    $checkRateLimitAction = Mockery::mock(CheckRateLimitAction::class);
    $checkRateLimitAction->shouldReceive('execute')->once();

    Config::shouldReceive('getAction')
        ->with('check_rate_limit', CheckRateLimitAction::class)
        ->once()
        ->andReturn($checkRateLimitAction);

    $trackRateLimitAction = Mockery::mock(TrackRateLimitUsageAction::class);
    $trackRateLimitAction->shouldReceive('execute')->once();

    Config::shouldReceive('getAction')
        ->with('track_rate_limit_usage', TrackRateLimitUsageAction::class)
        ->once()
        ->andReturn($trackRateLimitAction);

    $accountEntity = Mockery::mock('overload:'.Account::class);
    $accountEntity->shouldReceive('get')->once()->andReturn([]);

    $picqerConnection = Mockery::mock(Connection::class);

    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->once()
        ->andReturn($picqerConnection);
    $this->connection->shouldReceive('refresh')->once();

    // Execute
    $accounts = $this->action->execute($this->connection);

    expect($accounts)->toBeInstanceOf(Collection::class);
});

it('throws ConnectionException on API error', function () {
    // Mock rate limit checking
    $checkRateLimitAction = Mockery::mock(CheckRateLimitAction::class);
    $checkRateLimitAction->shouldReceive('execute')->once();

    Config::shouldReceive('getAction')
        ->with('check_rate_limit', CheckRateLimitAction::class)
        ->once()
        ->andReturn($checkRateLimitAction);

    // Mock Account entity to throw exception
    $accountEntity = Mockery::mock('overload:'.Account::class);
    $accountEntity->shouldReceive('get')
        ->once()
        ->andThrow(new Exception('API Error: Invalid request'));

    $picqerConnection = Mockery::mock(Connection::class);

    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->once()
        ->andReturn($picqerConnection);

    $this->action->execute($this->connection);
})->throws(
    ConnectionException::class,
    'Failed to retrieve accounts: API Error: Invalid request'
);

it('returns empty collection when no accounts found', function () {
    // Setup mocks
    $checkRateLimitAction = Mockery::mock(CheckRateLimitAction::class);
    $checkRateLimitAction->shouldReceive('execute')->once();

    Config::shouldReceive('getAction')
        ->with('check_rate_limit', CheckRateLimitAction::class)
        ->once()
        ->andReturn($checkRateLimitAction);

    $trackRateLimitAction = Mockery::mock(TrackRateLimitUsageAction::class);
    $trackRateLimitAction->shouldReceive('execute')->once();

    Config::shouldReceive('getAction')
        ->with('track_rate_limit_usage', TrackRateLimitUsageAction::class)
        ->once()
        ->andReturn($trackRateLimitAction);

    $accountEntity = Mockery::mock('overload:'.Account::class);
    $accountEntity->shouldReceive('get')
        ->once()
        ->andReturn([]);

    $picqerConnection = Mockery::mock(Connection::class);

    $this->connection = Mockery::mock($this->connection)->makePartial();
    $this->connection->shouldReceive('getPicqerConnection')
        ->once()
        ->andReturn($picqerConnection);

    $accounts = $this->action->execute($this->connection);

    expect($accounts)
        ->toBeInstanceOf(Collection::class)
        ->toBeEmpty();
});
