<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Events\DivisionsSynced;
use Skylence\ExactonlineLaravelApi\Exceptions\SyncException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Models\ExactDivision;
use Skylence\ExactonlineLaravelApi\Support\Config;

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
            'status' => (int) ($data['Status'] ?? 0),
            'blocking_status' => (int) ($data['BlockingStatus'] ?? 0),
            'started_at' => $this->parseDate($data['StartDate'] ?? null),
            'archived_at' => $this->parseDate($data['ArchiveDate'] ?? null),
            'synced_at' => now(),
        ];

        if ($existing) {
            $existing->update($attributes);

            return 'updated';
        }

        ExactDivision::create($attributes);

        return 'created';
    }

    /**
     * Parse Exact Online date format.
     */
    protected function parseDate(?string $date): ?\DateTime
    {
        if (empty($date)) {
            return null;
        }

        // Handle /Date(timestamp)/ format
        if (preg_match('/\/Date\((\d+)\)\//', $date, $matches)) {
            return (new \DateTime)->setTimestamp((int) ($matches[1] / 1000));
        }

        try {
            return new \DateTime($date);
        } catch (\Exception) {
            return null;
        }
    }
}
