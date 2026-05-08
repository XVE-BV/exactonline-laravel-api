<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Contact;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class GetContactAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single contact from Exact Online.
     *
     * @param  string  $contactId  The Exact Online contact ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $contactId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $contact = new Contact($picqerConnection);

            $result = $contact->find($contactId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved contact from Exact Online', [
                'connection_id' => $connection->id,
                'contact_id' => $contactId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve contact from Exact Online', [
                'connection_id' => $connection->id,
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve contact: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
