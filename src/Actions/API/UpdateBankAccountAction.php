<?php

declare(strict_types=1);

namespace XVE\Exactonline\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\BankAccount;
use XVE\Exactonline\Concerns\HandlesExactConnection;
use XVE\Exactonline\Concerns\ValidatesPayload;
use XVE\Exactonline\Exceptions\ConnectionException;
use XVE\Exactonline\Models\ExactConnection;

class UpdateBankAccountAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Update an existing bank account in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $bankAccountId  The Exact Online bank account ID (GUID)
     * @param  array<string, mixed>  $data  Bank account data to update
     * @return array<string, mixed> The updated bank account data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $bankAccountId, array $data): array
    {
        $this->validateUpdatePayload('BankAccount', $data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $bankAccount = new BankAccount($picqerConnection);
            $bankAccount->ID = $bankAccountId;

            foreach ($data as $key => $value) {
                $bankAccount->{$key} = $value;
            }

            $bankAccount->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Updated bank account in Exact Online', [
                'connection_id' => $connection->id,
                'bank_account_id' => $bankAccountId,
            ]);

            return $bankAccount->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to update bank account in Exact Online', [
                'connection_id' => $connection->id,
                'bank_account_id' => $bankAccountId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to update bank account: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
