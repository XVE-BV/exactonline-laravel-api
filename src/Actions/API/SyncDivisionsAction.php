<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use XVE\ExactonlineLaravelApi\Events\DivisionsSynced;
use XVE\ExactonlineLaravelApi\Exceptions\SyncException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;
use XVE\ExactonlineLaravelApi\Models\ExactDivision;
use XVE\ExactonlineLaravelApi\Support\Config;

class SyncDivisionsAction
{
    /**
     * Sync all available divisions from Exact Online to local database.
     *
     * @return array{created: int, updated: int, total: int}
     *
     * @throws SyncException
     */
    public function execute(ExactConnection $connection): array
    {
        try {
            // Fetch divisions from API
            $getDivisionsAction = Config::getAction(
                'get_divisions',
                GetDivisionsAction::class
            );

            $divisions = $getDivisionsAction->execute($connection);

            $created = 0;
            $updated = 0;

            foreach ($divisions as $divisionData) {
                $result = $this->syncDivision($connection, $divisionData);

                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'updated') {
                    $updated++;
                }
            }

            $this->syncActiveDivisionId($connection);

            Log::info('Synced divisions from Exact Online', [
                'connection_id' => $connection->id,
                'created' => $created,
                'updated' => $updated,
                'total' => $divisions->count(),
            ]);

            // Dispatch event
            event(new DivisionsSynced($connection, $created, $updated, $divisions->count()));

            return [
                'created' => $created,
                'updated' => $updated,
                'total' => $divisions->count(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to sync divisions from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof SyncException) {
                throw $e;
            }

            throw SyncException::pullFailed(
                'Division',
                (string) $connection->id,
                $e->getMessage()
            );
        }
    }

    /**
     * Sync a single division.
     *
     * @param  array<string, mixed>  $data
     * @return string 'created', 'updated', or 'unchanged'
     */
    protected function syncDivision(ExactConnection $connection, array $data): string
    {
        $code = (int) ($data['Code'] ?? 0);

        if ($code === 0) {
            return 'unchanged';
        }

        $existing = ExactDivision::query()
            ->where('connection_id', $connection->id)
            ->where('code', $code)
            ->first();

        $status = (int) ($data['Status'] ?? 0);

        $attributes = [
            'connection_id' => $connection->id,
            'code' => $code,
            'description' => $data['Description'] ?? null,
            'hid' => isset($data['HID']) ? (string) $data['HID'] : null,
            'customer_code' => $data['CustomerCode'] ?? null,
            'customer_name' => $data['CustomerName'] ?? null,
            'country' => $data['Country'] ?? null,
            'currency' => $data['Currency'] ?? null,
            'vat_number' => $data['VATNumber'] ?? null,
            'is_main' => (bool) ($data['Main'] ?? false),
            'status' => $status,
            'blocking_status' => (int) ($data['BlockingStatus'] ?? 0),
            'started_at' => $this->parseDate($data['StartDate'] ?? null),
            'archived_at' => $this->resolveArchivedAt($status, $data['ArchiveDate'] ?? null, $existing),
            'synced_at' => now(),
        ];

        if ($existing) {
            $existing->update($attributes);

            return 'updated';
        }

        ExactDivision::create($attributes);

        return 'created';
    }

    protected function syncActiveDivisionId(ExactConnection $connection): void
    {
        $divisionId = null;

        if ($connection->division !== null) {
            $divisionId = ExactDivision::query()
                ->where('connection_id', $connection->id)
                ->where('code', $connection->division)
                ->value('id');
        }

        $connection->forceFill(['division_id' => $divisionId === null ? null : (int) $divisionId])->save();
    }

    /**
     * Resolve the local archived timestamp from Exact's ArchiveDate field.
     */
    protected function resolveArchivedAt(int $status, mixed $archiveDate, ?ExactDivision $existing): ?\DateTime
    {
        if ($status !== 1) {
            return null;
        }

        return $this->parseDate($archiveDate)
            ?? $existing?->archived_at?->toDateTime()
            ?? now()->toDateTime();
    }

    /**
     * Parse Exact Online date format.
     */
    protected function parseDate(mixed $date): ?\DateTime
    {
        if ($date instanceof \DateTime) {
            return $date;
        }

        if ($date instanceof \DateTimeInterface) {
            return \DateTime::createFromInterface($date);
        }

        if (! is_string($date) || trim($date) === '') {
            return null;
        }

        // Handle Exact/Picqer OData /Date(timestamp)/ format.
        if (preg_match('/\/Date\((-?\d+)\)\//', $date, $matches) === 1) {
            $timestamp = (int) ((int) $matches[1] / 1000);

            if ($timestamp <= 0) {
                return null;
            }

            return (new \DateTime)->setTimestamp($timestamp);
        }

        try {
            $parsed = new \DateTime($date);

            return (int) $parsed->format('Y') <= 1 ? null : $parsed;
        } catch (\Exception) {
            return null;
        }
    }
}
