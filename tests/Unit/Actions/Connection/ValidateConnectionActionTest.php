<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XVE\ExactonlineLaravelApi\Actions\Connection\ValidateConnectionAction;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;
use XVE\ExactonlineLaravelApi\Models\ExactDivision;

uses(RefreshDatabase::class);

it('resolves division id when validation stores current division from Exact', function () {
    $connection = ExactConnection::factory()->create([
        'access_token' => 'access-token',
        'refresh_token' => 'refresh-token',
        'token_expires_at' => now()->addMinutes(10)->timestamp,
        'is_active' => true,
        'division' => null,
    ]);

    $division = ExactDivision::query()->create([
        'connection_id' => $connection->id,
        'code' => 1284243,
        'description' => 'Synced division',
    ]);

    $result = (new FakeCurrentDivisionValidateConnectionAction)->execute($connection);

    $connection->refresh();

    expect($result['valid'])->toBeTrue()
        ->and($connection->division)->toBe('1284243')
        ->and($connection->division_id)->toBe($division->id)
        ->and($connection->activeDivision?->is($division))->toBeTrue();
});

class FakeCurrentDivisionValidateConnectionAction extends ValidateConnectionAction
{
    /**
     * @return array<int, object>
     */
    protected function getCurrentUser(ExactConnection $connection): array
    {
        return [(object) ['CurrentDivision' => '1284243']];
    }

    /**
     * @return array{accessible: bool, error: string|null}
     */
    protected function verifyDivisionAccess(ExactConnection $connection): array
    {
        return [
            'accessible' => true,
            'error' => null,
        ];
    }
}
