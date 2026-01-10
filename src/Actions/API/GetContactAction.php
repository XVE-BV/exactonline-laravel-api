<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Contact;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

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
