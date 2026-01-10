<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Journal;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class UpdateJournalAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Update an existing journal in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $journalId  The journal ID (GUID) to update
     * @param  array{
     *     Code?: string,
     *     Description?: string,
     *     Type?: int|null,
     *     Bank?: string|null,
     *     Currency?: string|null,
     *     GLAccount?: string|null,
     *     GLAccountType?: int|null,
     *     PaymentInTransitAccount?: string|null,
     *     PaymentServiceAccountIdentifier?: string|null,
     *     PaymentServiceProvider?: int|null,
     *     PaymentServiceProviderName?: string|null
     * }  $data  Journal data to update
     * @return array<string, mixed> The updated journal data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $journalId, array $data): array
    {
        $this->validateUpdatePayload('Journal', $data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $journal = new Journal($picqerConnection);

            $existingJournal = $journal->find($journalId);

            if ($existingJournal === null) {
                throw new ConnectionException("Journal with ID {$journalId} not found");
            }

            foreach ($data as $key => $value) {
                $existingJournal->{$key} = $value;
            }

            $existingJournal->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Updated journal in Exact Online', [
                'connection_id' => $connection->id,
                'journal_id' => $journalId,
                'updated_fields' => array_keys($data),
            ]);

            return $existingJournal->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to update journal in Exact Online', [
                'connection_id' => $connection->id,
                'journal_id' => $journalId,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new ConnectionException(
                'Failed to update journal: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
