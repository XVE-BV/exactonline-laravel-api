<?php

declare(strict_types=1);

namespace XVE\Exactonline\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Address;
use XVE\Exactonline\Concerns\HandlesExactConnection;
use XVE\Exactonline\Exceptions\ConnectionException;
use XVE\Exactonline\Models\ExactConnection;

class GetAddressAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single address from Exact Online.
     *
     * @param  string  $addressId  The Exact Online address ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $addressId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $address = new Address($picqerConnection);

            $result = $address->find($addressId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved address from Exact Online', [
                'connection_id' => $connection->id,
                'address_id' => $addressId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve address from Exact Online', [
                'connection_id' => $connection->id,
                'address_id' => $addressId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve address: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
