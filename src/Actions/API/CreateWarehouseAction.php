<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Warehouse;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreateWarehouseAction
{
    use HandlesExactConnection;

    /**
     * Create a new warehouse in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array{
     *     Code: string,
     *     Description: string,
     *     Main?: bool|null,
     *     ManagerUser?: string|null,
     *     UseStorageLocations?: int|null
     * }  $data  Warehouse data following Exact Online's schema
     * @return array<string, mixed> The created warehouse data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateWarehouseData($data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $warehouse = new Warehouse($picqerConnection);

            foreach ($data as $key => $value) {
                $warehouse->{$key} = $value;
            }

            $warehouse->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created warehouse in Exact Online', [
                'connection_id' => $connection->id,
                'warehouse_id' => $warehouse->ID,
                'warehouse_code' => $warehouse->Code,
                'warehouse_description' => $warehouse->Description,
            ]);

            return $warehouse->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create warehouse in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new ConnectionException(
                'Failed to create warehouse: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Validate required warehouse data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateWarehouseData(array $data): void
    {
        if (empty($data['Code'])) {
            throw ConnectionException::invalidConfiguration(
                'Warehouse code is required'
            );
        }

        if (empty($data['Description'])) {
            throw ConnectionException::invalidConfiguration(
                'Warehouse description is required'
            );
        }
    }
}
