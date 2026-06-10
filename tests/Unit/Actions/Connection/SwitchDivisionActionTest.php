<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XVE\ExactonlineLaravelApi\Actions\Connection\SwitchDivisionAction;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;
use XVE\ExactonlineLaravelApi\Models\ExactDivision;

uses(RefreshDatabase::class);

it('updates division id when switching to a synced division code', function () {
    $connection = ExactConnection::factory()->create([
        'division' => '1046400',
        'metadata' => null,
    ]);

    ExactDivision::query()->create([
        'connection_id' => $connection->id,
        'code' => 1046400,
        'description' => 'Old division',
    ]);
    $newDivision = ExactDivision::query()->create([
        'connection_id' => $connection->id,
        'code' => 1284243,
        'description' => 'New division',
    ]);

    $switchedConnection = app(SwitchDivisionAction::class)->execute($connection, 1284243, false);

    expect($switchedConnection->division)->toBe('1284243')
        ->and($switchedConnection->division_id)->toBe($newDivision->id)
        ->and($switchedConnection->activeDivision?->is($newDivision))->toBeTrue();
});

it('clears division id when switching before divisions are synced', function () {
    $connection = ExactConnection::factory()->create([
        'division' => '1046400',
        'metadata' => null,
    ]);
    $oldDivision = ExactDivision::query()->create([
        'connection_id' => $connection->id,
        'code' => 1046400,
        'description' => 'Old division',
    ]);
    $connection->update(['division_id' => $oldDivision->id]);

    $switchedConnection = app(SwitchDivisionAction::class)->execute($connection, 1284243, false);

    expect($switchedConnection->division)->toBe('1284243')
        ->and($switchedConnection->division_id)->toBeNull();
});
