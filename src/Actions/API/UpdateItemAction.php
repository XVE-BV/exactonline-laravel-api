<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Item;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

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
