<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Warehouse;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class UpdateWarehouseAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Update an existing warehouse in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $warehouseId  The Exact Online warehouse ID (GUID)
     * @param  array<string, mixed>  $data  Warehouse data to update
     * @return array<string, mixed> The updated warehouse data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $warehouseId, array $data): array
    {
        $this->validateUpdatePayload('Warehouse', $data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $warehouse = new Warehouse($picqerConnection);
            $warehouse->ID = $warehouseId;

            foreach ($data as $key => $value) {
                $warehouse->{$key} = $value;
            }

            $warehouse->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Updated warehouse in Exact Online', [
                'connection_id' => $connection->id,
                'warehouse_id' => $warehouseId,
            ]);

            return $warehouse->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to update warehouse in Exact Online', [
                'connection_id' => $connection->id,
                'warehouse_id' => $warehouseId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to update warehouse: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
