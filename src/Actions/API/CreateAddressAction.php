<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Address;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreateAddressAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Create a new address in Exact Online.
     *
     * @param  array{
     *     Account: string,
     *     Type: int,
     *     AddressLine1: string,
     *     AddressLine2?: string|null,
     *     AddressLine3?: string|null,
     *     City?: string|null,
     *     Country?: string|null,
     *     CountryName?: string|null,
     *     Postcode?: string|null,
     *     State?: string|null,
     *     StateDescription?: string|null,
     *     Contact?: string|null,
     *     ContactName?: string|null,
     *     Fax?: string|null,
     *     Mailbox?: string|null,
     *     Main?: bool|null,
     *     Phone?: string|null,
     *     PhoneExtension?: string|null
     * }  $data
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateCreatePayload('Address', $data);
        $this->validateData($data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $address = new Address($picqerConnection);

            foreach ($data as $key => $value) {
                $address->{$key} = $value;
            }

            $address->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created address in Exact Online', [
                'connection_id' => $connection->id,
                'address_id' => $address->ID,
                'address_type' => $address->Type,
            ]);

            return $address->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create address in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to create address: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateData(array $data): void
    {
        if (empty($data['Type'])) {
            throw ConnectionException::invalidConfiguration(
                'Address type is required'
            );
        }

        if (empty($data['AddressLine1'])) {
            throw ConnectionException::invalidConfiguration(
                'Address line 1 is required'
            );
        }
    }
}
