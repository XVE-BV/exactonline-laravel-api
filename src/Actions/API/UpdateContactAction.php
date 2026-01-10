<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Contact;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class UpdateContactAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Update an existing contact in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $contactId  The Exact Online contact ID (GUID)
     * @param  array<string, mixed>  $data  Contact data to update
     * @return array<string, mixed> The updated contact data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $contactId, array $data): array
    {
        $this->validateUpdatePayload('Contact', $data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $contact = new Contact($picqerConnection);
            $contact->ID = $contactId;

            foreach ($data as $key => $value) {
                $contact->{$key} = $value;
            }

            $contact->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Updated contact in Exact Online', [
                'connection_id' => $connection->id,
                'contact_id' => $contactId,
            ]);

            return $contact->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to update contact in Exact Online', [
                'connection_id' => $connection->id,
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to update contact: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
