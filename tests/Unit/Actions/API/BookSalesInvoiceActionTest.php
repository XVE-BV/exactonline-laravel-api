<?php

declare(strict_types=1);

use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\PrintedSalesInvoice;
use XVE\ExactonlineLaravelApi\Actions\API\BookSalesInvoiceAction;
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

    $this->action = Mockery::mock(BookSalesInvoiceAction::class)->makePartial()->shouldAllowMockingProtectedMethods();

    $checkRateLimitAction = Mockery::mock(CheckRateLimitAction::class);
    $checkRateLimitAction->shouldReceive('execute')->andReturn([]);
    $this->app->instance(CheckRateLimitAction::class, $checkRateLimitAction);

    $trackRateLimitAction = Mockery::mock(TrackRateLimitUsageAction::class);
    $trackRateLimitAction->shouldReceive('execute')->andReturn([]);
    $this->app->instance(TrackRateLimitUsageAction::class, $trackRateLimitAction);
});

function bookConnectionMock(object $test, Connection $picqerConnection): ExactConnection
{
    $connection = Mockery::mock($test->connection)->makePartial();
    $connection->shouldReceive('getPicqerConnection')->once()->andReturn($picqerConnection);
    $connection->shouldReceive('markAsUsed')->andReturn(null);

    return $connection;
}

it('books a draft invoice and returns its attributes', function () {
    $printed = Mockery::mock(PrintedSalesInvoice::class)->makePartial();
    $printed->shouldReceive('save')->once()->andReturnSelf();
    $printed->shouldReceive('attributes')->once()->andReturn([
        'InvoiceID' => 'draft-guid',
        'Document' => 'doc-guid',
        'DocumentCreationError' => '',
    ]);

    $this->action->shouldReceive('makePrintedSalesInvoice')->once()->andReturn($printed);

    $picqerConnection = Mockery::mock(Connection::class);
    $connection = bookConnectionMock($this, $picqerConnection);

    $result = $this->action->execute($connection, 'draft-guid');

    expect($result)
        ->toBeArray()
        ->toHaveKey('Document', 'doc-guid')
        ->and($result['InvoiceID'])->toBe('draft-guid');

    // The Send* flags are forced off so booking never auto-transmits.
    expect($printed->SendEmailToCustomer)->toBeFalse()
        ->and($printed->SendInvoiceViaPeppol)->toBeFalse()
        ->and($printed->SendOutputBasedOnAccount)->toBeFalse()
        ->and($printed->InvoiceID)->toBe('draft-guid');
});

it('merges caller options into the booking payload', function () {
    $printed = Mockery::mock(PrintedSalesInvoice::class)->makePartial();
    $printed->shouldReceive('save')->once()->andReturnSelf();
    $printed->shouldReceive('attributes')->once()->andReturn(['Document' => 'doc-guid']);

    $this->action->shouldReceive('makePrintedSalesInvoice')->once()->andReturn($printed);

    $picqerConnection = Mockery::mock(Connection::class);
    $connection = bookConnectionMock($this, $picqerConnection);

    $this->action->execute($connection, 'draft-guid', [
        'DocumentLayout' => 'layout-guid',
        'InvoiceDate' => '2026-01-15',
    ]);

    expect($printed->DocumentLayout)->toBe('layout-guid')
        ->and($printed->InvoiceDate)->toBe('2026-01-15');
});

it('throws when Exact reports a document creation error', function () {
    $printed = Mockery::mock(PrintedSalesInvoice::class)->makePartial();
    $printed->shouldReceive('save')->once()->andReturnSelf();
    $printed->shouldReceive('attributes')->once()->andReturn([
        'InvoiceID' => 'draft-guid',
        'DocumentCreationError' => 'Layout not found',
    ]);

    $this->action->shouldReceive('makePrintedSalesInvoice')->once()->andReturn($printed);

    $picqerConnection = Mockery::mock(Connection::class);
    $connection = bookConnectionMock($this, $picqerConnection);

    $this->action->execute($connection, 'draft-guid');
})->throws(ConnectionException::class, 'Failed to book sales invoice: Exact reported a document creation error while booking: Layout not found');

it('throws ConnectionException on API error', function () {
    $printed = Mockery::mock(PrintedSalesInvoice::class)->makePartial();
    $printed->shouldReceive('save')->once()->andThrow(new Exception('API Error: Invalid request'));

    $this->action->shouldReceive('makePrintedSalesInvoice')->once()->andReturn($printed);

    $picqerConnection = Mockery::mock(Connection::class);
    $connection = bookConnectionMock($this, $picqerConnection);

    $this->action->execute($connection, 'draft-guid');
})->throws(ConnectionException::class, 'Failed to book sales invoice: API Error: Invalid request');
