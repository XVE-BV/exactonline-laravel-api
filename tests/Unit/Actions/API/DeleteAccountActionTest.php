<?php

declare(strict_types=1);

use Picqer\Financials\Exact\Connection;
use XVE\ExactonlineLaravelApi\Actions\API\DeleteAccountAction;
use XVE\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use XVE\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;
use XVE\ExactonlineLaravelApi\Support\Config;

beforeEach(function () {
    $this->connection = ExactConnection::factory()->create([
        'access_token' => encrypt('valid-token'),
        'refresh_token' => encrypt('valid-refresh'),
        'token_expires_at' => now()->addMinutes(10)->timestamp,
        'is_active' => true,
    ]);

    $checkRateLimitAction = Mockery::mock(CheckRateLimitAction::class);
    $checkRateLimitAction->shouldReceive('execute')->andReturn([]);
    $this->app->instance(CheckRateLimitAction::class, $checkRateLimitAction);

    $trackRateLimitAction = Mockery::mock(TrackRateLimitUsageAction::class);
    $trackRateLimitAction->shouldReceive('execute')->andReturn([]);
    $this->app->instance(TrackRateLimitUsageAction::class, $trackRateLimitAction);
});

function deleteConnectionMock(object $test, Connection $picqerConnection): ExactConnection
{
    $connection = Mockery::mock($test->connection)->makePartial();
    $connection->shouldReceive('getPicqerConnection')->once()->andReturn($picqerConnection);
    $connection->shouldReceive('markAsUsed')->andReturn(null);

    return $connection;
}

it('sends a DELETE for the account to Exact', function () {
    $picqerConnection = Mockery::mock(Connection::class);
    $picqerConnection->shouldReceive('delete')
        ->once()
        ->with("crm/Accounts(guid'account-guid')")
        ->andReturn([]);

    $result = (new DeleteAccountAction)->execute(deleteConnectionMock($this, $picqerConnection), 'account-guid');

    expect($result)->toBeTrue();
});

it('throws a ConnectionException with Exact\'s message when Exact refuses', function () {
    $picqerConnection = Mockery::mock(Connection::class);
    $picqerConnection->shouldReceive('delete')
        ->once()
        ->andThrow(new RuntimeException('Account is in use', 400));

    expect(fn () => (new DeleteAccountAction)->execute(deleteConnectionMock($this, $picqerConnection), 'account-guid'))
        ->toThrow(ConnectionException::class, 'Failed to delete account: Account is in use');
});

it('is registered in the action config', function () {
    expect(Config::getAction('delete_account', DeleteAccountAction::class))->toBeInstanceOf(DeleteAccountAction::class);
});
