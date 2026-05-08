<?php

declare(strict_types=1);

namespace XVE\Exactonline\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Warehouse;
use XVE\Exactonline\Concerns\HandlesExactConnection;
use XVE\Exactonline\Exceptions\ConnectionException;
use XVE\Exactonline\Models\ExactConnection;

class GetWarehouseAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single warehouse from Exact Online.
     *
     * @param  string  $warehouseId  The Exact Online warehouse ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $warehouseId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $warehouse = new Warehouse($picqerConnection);

            $result = $warehouse->find($warehouseId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved warehouse from Exact Online', [
                'connection_id' => $connection->id,
                'warehouse_id' => $warehouseId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve warehouse from Exact Online', [
                'connection_id' => $connection->id,
                'warehouse_id' => $warehouseId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve warehouse: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
