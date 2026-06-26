<?php

declare(strict_types=1);

use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\SalesInvoiceLine;
use XVE\ExactonlineLaravelApi\Actions\API\GetSalesInvoiceLinesAction;
use XVE\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use XVE\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

beforeEach(function () {
    $this->connection = ExactConnection::factory()->create([
        'access_token' => encrypt('valid-token'),
        'refresh_token' => encrypt('valid-refresh'),
        'token_expires_at' => now()->addMinutes(10)->timestamp,
        'is_active' => true,
    ]);

    $this->action = Mockery::mock(GetSalesInvoiceLinesAction::class)->makePartial()->shouldAllowMockingProtectedMethods();

    $checkRateLimitAction = Mockery::mock(CheckRateLimitAction::class);
    $checkRateLimitAction->shouldReceive('execute')->andReturn([]);
    $this->app->instance(CheckRateLimitAction::class, $checkRateLimitAction);

    $trackRateLimitAction = Mockery::mock(TrackRateLimitUsageAction::class);
    $trackRateLimitAction->shouldReceive('execute')->andReturn([]);
    $this->app->instance(TrackRateLimitUsageAction::class, $trackRateLimitAction);
});

function linesConnectionMock(object $test, Connection $picqerConnection): ExactConnection
{
    $connection = Mockery::mock($test->connection)->makePartial();
    $connection->shouldReceive('getPicqerConnection')->once()->andReturn($picqerConnection);
    $connection->shouldReceive('markAsUsed')->andReturn(null);

    return $connection;
}

it('retrieves and maps invoice lines ordered by line number', function () {
    $line1 = Mockery::mock();
    $line1->shouldReceive('attributes')->once()->andReturn([
        'LineNumber' => 1,
        'AmountFC' => 100.0,
        'VATPercentage' => 0.21,
        'VATAmountFC' => 21.0,
    ]);
    $line2 = Mockery::mock();
    $line2->shouldReceive('attributes')->once()->andReturn([
        'LineNumber' => 2,
        'AmountFC' => 50.0,
        'VATPercentage' => 0.21,
        'VATAmountFC' => 10.5,
    ]);

    $lineEntity = Mockery::mock(SalesInvoiceLine::class);
    $lineEntity->shouldReceive('filter')
        ->once()
        ->with("InvoiceID eq guid'inv-guid'", '', '', ['$orderby' => 'LineNumber'])
        ->andReturn([$line1, $line2]);

    $this->action->shouldReceive('makeSalesInvoiceLine')->once()->andReturn($lineEntity);

    $picqerConnection = Mockery::mock(Connection::class);
    $connection = linesConnectionMock($this, $picqerConnection);

    $lines = $this->action->execute($connection, 'inv-guid');

    expect($lines)
        ->toBeArray()
        ->toHaveCount(2)
        ->and($lines[0]['LineNumber'])->toBe(1)
        ->and($lines[1]['AmountFC'])->toBe(50.0);
});

it('returns an empty array when the invoice has no lines', function () {
    $lineEntity = Mockery::mock(SalesInvoiceLine::class);
    $lineEntity->shouldReceive('filter')->once()->andReturn([]);

    $this->action->shouldReceive('makeSalesInvoiceLine')->once()->andReturn($lineEntity);

    $picqerConnection = Mockery::mock(Connection::class);
    $connection = linesConnectionMock($this, $picqerConnection);

    expect($this->action->execute($connection, 'inv-guid'))->toBe([]);
});

it('throws ConnectionException on API error', function () {
    $lineEntity = Mockery::mock(SalesInvoiceLine::class);
    $lineEntity->shouldReceive('filter')->once()->andThrow(new Exception('API Error: Invalid request'));

    $this->action->shouldReceive('makeSalesInvoiceLine')->once()->andReturn($lineEntity);

    $picqerConnection = Mockery::mock(Connection::class);
    $connection = linesConnectionMock($this, $picqerConnection);

    $this->action->execute($connection, 'inv-guid');
})->throws(ConnectionException::class, 'Failed to retrieve sales invoice lines: API Error: Invalid request');
