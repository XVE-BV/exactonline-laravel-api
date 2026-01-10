<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Address;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class UpdateAddressAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Update an existing address in Exact Online.
     *
     * @param  string  $addressId  The Exact Online address ID (GUID)
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $addressId, array $data): array
    {
        $this->validateUpdatePayload('Address', $data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $address = new Address($picqerConnection);
            $address->ID = $addressId;

            foreach ($data as $key => $value) {
                $address->{$key} = $value;
            }

            $address->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Updated address in Exact Online', [
                'connection_id' => $connection->id,
                'address_id' => $addressId,
            ]);

            return $address->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to update address in Exact Online', [
                'connection_id' => $connection->id,
                'address_id' => $addressId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to update address: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
