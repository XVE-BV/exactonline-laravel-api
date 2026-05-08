<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Journal;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class GetJournalAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single journal from Exact Online.
     *
     * @param  string  $journalId  The Exact Online journal ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $journalId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $journal = new Journal($picqerConnection);

            $result = $journal->find($journalId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved journal from Exact Online', [
                'connection_id' => $connection->id,
                'journal_id' => $journalId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve journal from Exact Online', [
                'connection_id' => $connection->id,
                'journal_id' => $journalId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve journal: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
