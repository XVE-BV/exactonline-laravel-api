<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Contact;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreateContactAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Create a new contact in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array{
     *     Account: string,
     *     FirstName?: string|null,
     *     LastName?: string|null,
     *     FullName?: string|null,
     *     JobTitleDescription?: string|null,
     *     Email?: string|null,
     *     Phone?: string|null,
     *     Mobile?: string|null,
     *     Fax?: string|null,
     *     Gender?: string|null,
     *     Title?: string|null,
     *     Initials?: string|null,
     *     MiddleName?: string|null,
     *     Salutation?: string|null,
     *     Language?: string|null,
     *     Notes?: string|null,
     *     IsMainContact?: bool|null,
     *     City?: string|null,
     *     Country?: string|null,
     *     AddressLine1?: string|null,
     *     AddressLine2?: string|null,
     *     Postcode?: string|null,
     *     State?: string|null
     * }  $data  Contact data following Exact Online's schema
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
