<?php

declare(strict_types=1);

namespace XVE\Exactonline\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\ItemGroup;
use XVE\Exactonline\Concerns\HandlesExactConnection;
use XVE\Exactonline\Exceptions\ConnectionException;
use XVE\Exactonline\Models\ExactConnection;

class GetItemGroupAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single item group from Exact Online.
     *
     * @param  string  $itemGroupId  The Exact Online item group ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $itemGroupId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $itemGroup = new ItemGroup($picqerConnection);

            $result = $itemGroup->find($itemGroupId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved item group from Exact Online', [
                'connection_id' => $connection->id,
                'item_group_id' => $itemGroupId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve item group from Exact Online', [
                'connection_id' => $connection->id,
                'item_group_id' => $itemGroupId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve item group: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
