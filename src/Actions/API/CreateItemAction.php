<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Item;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreateItemAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Create a new item in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array<string, mixed>  $data  The entity data
     * @return array<string, mixed> The created item data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateCreatePayload('Item', $data);
        $this->validateItemData($data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $item = new Item($picqerConnection);

            foreach ($data as $key => $value) {
                $item->{$key} = $value;
            }

            $item->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created item in Exact Online', [
                'connection_id' => $connection->id,
                'item_id' => $item->ID,
                'item_code' => $item->Code,
                'item_description' => $item->Description,
            ]);

            return $item->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create item in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new ConnectionException(
                'Failed to create item: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Validate required item data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateItemData(array $data): void
    {
        if (empty($data['Code'])) {
            throw ConnectionException::invalidConfiguration(
                'Item code is required'
            );
        }

        if (empty($data['Description'])) {
            throw ConnectionException::invalidConfiguration(
                'Item description is required'
            );
        }
    }
}
