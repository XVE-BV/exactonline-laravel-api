<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Journal;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreateJournalAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Create a new journal in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array{
     *     Code: string,
     *     Description: string,
     *     Type?: int|null,
     *     Bank?: string|null,
     *     Currency?: string|null,
     *     GLAccount?: string|null,
     *     GLAccountType?: int|null,
     *     PaymentInTransitAccount?: string|null,
     *     PaymentServiceAccountIdentifier?: string|null,
     *     PaymentServiceProvider?: int|null,
     *     PaymentServiceProviderName?: string|null
     * }  $data  Journal data following Exact Online's schema
     * @return array<string, mixed> The created journal data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateCreatePayload('Journal', $data);
        $this->validateJournalData($data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $journal = new Journal($picqerConnection);

            foreach ($data as $key => $value) {
                $journal->{$key} = $value;
            }

            $journal->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created journal in Exact Online', [
                'connection_id' => $connection->id,
                'journal_id' => $journal->ID,
                'journal_code' => $journal->Code,
                'journal_description' => $journal->Description,
            ]);

            return $journal->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create journal in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new ConnectionException(
                'Failed to create journal: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Validate required journal data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateJournalData(array $data): void
    {
        if (empty($data['Code'])) {
            throw ConnectionException::invalidConfiguration(
                'Journal Code is required'
            );
        }

        if (empty($data['Description'])) {
            throw ConnectionException::invalidConfiguration(
                'Journal Description is required'
            );
        }
    }
}
