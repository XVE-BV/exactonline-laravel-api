<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Item;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class GetItemAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single item from Exact Online.
     *
     * @param  string  $itemId  The Exact Online item ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $itemId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $item = new Item($picqerConnection);

            $result = $item->find($itemId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved item from Exact Online', [
                'connection_id' => $connection->id,
                'item_id' => $itemId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve item from Exact Online', [
                'connection_id' => $connection->id,
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve item: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
