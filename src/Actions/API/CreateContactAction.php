<?php

declare(strict_types=1);

namespace XVE\Exactonline\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Contact;
use XVE\Exactonline\Concerns\HandlesExactConnection;
use XVE\Exactonline\Concerns\ValidatesPayload;
use XVE\Exactonline\Exceptions\ConnectionException;
use XVE\Exactonline\Models\ExactConnection;

class CreateContactAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Create a new contact in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array<string, mixed>  $data  The entity data
     * @return array<string, mixed> The created contact data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateCreatePayload('Contact', $data);
        $this->validateContactData($data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $contact = new Contact($picqerConnection);

            foreach ($data as $key => $value) {
                $contact->{$key} = $value;
            }

            $contact->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created contact in Exact Online', [
                'connection_id' => $connection->id,
                'contact_id' => $contact->ID,
                'contact_name' => $contact->FullName ?? ($contact->FirstName.' '.$contact->LastName),
                'account_id' => $contact->Account,
            ]);

            return $contact->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create contact in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new ConnectionException(
                'Failed to create contact: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Validate required contact data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateContactData(array $data): void
    {
        if (empty($data['Account'])) {
            throw ConnectionException::invalidConfiguration(
                'Account ID is required for contacts'
            );
        }

        if (! empty($data['Email']) && ! filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
            throw ConnectionException::invalidConfiguration(
                'Invalid email format provided'
            );
        }
    }
}
