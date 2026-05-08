<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Item;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class UpdateItemAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Update an existing item in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $itemId  The Exact Online item ID (GUID)
     * @param  array<string, mixed>  $data  Item data to update
     * @return array<string, mixed> The updated item data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $itemId, array $data): array
    {
        $this->validateUpdatePayload('Item', $data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $item = new Item($picqerConnection);
            $item->ID = $itemId;

            foreach ($data as $key => $value) {
                $item->{$key} = $value;
            }

            $item->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Updated item in Exact Online', [
                'connection_id' => $connection->id,
                'item_id' => $itemId,
            ]);

            return $item->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to update item in Exact Online', [
                'connection_id' => $connection->id,
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to update item: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
