<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use XVE\ExactonlineLaravelApi\Actions\API\GetDivisionsAction;
use XVE\ExactonlineLaravelApi\Actions\API\SyncDivisionsAction;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;
use XVE\ExactonlineLaravelApi\Models\ExactDivision;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->connection = ExactConnection::factory()->create();
});

it('maps active and archived division archive dates from Exact data', function () {
    config()->set('exactonline-laravel-api.actions.get_divisions', FakeSyncDivisionsGetDivisionsAction::class);

    FakeSyncDivisionsGetDivisionsAction::$divisions = [
        [
            'Code' => 1046400,
            'Description' => 'Active division',
            'Main' => false,
            'Status' => 0,
            'BlockingStatus' => 0,
            'StartDate' => '2024-01-01 00:00:00',
            'ArchiveDate' => '2026-06-09 09:08:29',
        ],
        [
            'Code' => 1284243,
            'Description' => 'Archived division',
            'Main' => true,
            'Status' => 1,
            'BlockingStatus' => 0,
            'StartDate' => '2023-01-01 00:00:00',
            'ArchiveDate' => '2024-06-01 12:34:56',
        ],
    ];

    $result = app(SyncDivisionsAction::class)->execute($this->connection);

    $activeDivision = ExactDivision::query()
        ->where('connection_id', $this->connection->id)
        ->where('code', 1046400)
        ->firstOrFail();
    $archivedDivision = ExactDivision::query()
        ->where('connection_id', $this->connection->id)
        ->where('code', 1284243)
        ->firstOrFail();

    expect($result)->toBe([
        'created' => 2,
        'updated' => 0,
        'total' => 2,
    ])
        ->and($activeDivision->status)->toBe(0)
        ->and($activeDivision->archived_at)->toBeNull()
        ->and($archivedDivision->status)->toBe(1)
        ->and($archivedDivision->archived_at?->toDateTimeString())->toBe('2024-06-01 12:34:56');
});

class FakeSyncDivisionsGetDivisionsAction extends GetDivisionsAction
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public static array $divisions = [];

    /**
     * @param  array<string, mixed>  $options
     * @return Collection<int, array<string, mixed>>
     */
    public function execute(ExactConnection $connection, array $options = []): Collection
    {
        return collect(self::$divisions);
    }
}
