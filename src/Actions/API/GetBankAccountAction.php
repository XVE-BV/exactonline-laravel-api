<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\BankAccount;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetBankAccountAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single bank account from Exact Online.
     *
     * @param  string  $bankAccountId  The Exact Online bank account ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $bankAccountId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $bankAccount = new BankAccount($picqerConnection);

            $result = $bankAccount->find($bankAccountId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved bank account from Exact Online', [
                'connection_id' => $connection->id,
                'bank_account_id' => $bankAccountId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve bank account from Exact Online', [
                'connection_id' => $connection->id,
                'bank_account_id' => $bankAccountId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve bank account: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
