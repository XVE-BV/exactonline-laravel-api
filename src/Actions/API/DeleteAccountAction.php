<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Account;
use Picqer\Financials\Exact\Connection;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class DeleteAccountAction
{
    use HandlesExactConnection;

    /**
     * Delete an account from Exact Online.
     *
     * Exact may refuse the delete (for example for an account that is still
     * in use); that refusal is thrown as a ConnectionException carrying
     * Exact's message.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $accountId  The account ID (GUID) to delete
     * @return bool True if the account was deleted
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $accountId): bool
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $account = $this->makeAccount($picqerConnection);
            $account->ID = $accountId;

            $account->delete();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Deleted account from Exact Online', [
                'connection_id' => $connection->id,
                'account_id' => $accountId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete account from Exact Online', [
                'connection_id' => $connection->id,
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to delete account: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    protected function makeAccount(Connection $picqerConnection): Account
    {
        return new Account($picqerConnection);
    }
}
