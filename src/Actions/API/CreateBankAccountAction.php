<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\BankAccount;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreateBankAccountAction
{
    use HandlesExactConnection;

    /**
     * Create a new bank account in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array{
     *     Account: string,
     *     BankAccount?: string|null,
     *     BankAccountHolderName?: string|null,
     *     BankDescription?: string|null,
     *     BankName?: string|null,
     *     BICCode?: string|null,
     *     Description?: string|null,
     *     IBAN?: string|null,
     *     Main?: bool|null,
     *     Type?: string|null
     * }  $data  Bank account data following Exact Online's schema
     * @return array<string, mixed> The created bank account data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateBankAccountData($data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $bankAccount = new BankAccount($picqerConnection);

            foreach ($data as $key => $value) {
                $bankAccount->{$key} = $value;
            }

            $bankAccount->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created bank account in Exact Online', [
                'connection_id' => $connection->id,
                'bank_account_id' => $bankAccount->ID,
                'account_id' => $bankAccount->Account,
                'iban' => $bankAccount->IBAN ?? 'N/A',
            ]);

            return $bankAccount->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create bank account in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new ConnectionException(
                'Failed to create bank account: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Validate required bank account data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateBankAccountData(array $data): void
    {
        if (empty($data['Account'])) {
            throw ConnectionException::invalidConfiguration(
                'Account ID is required for bank accounts'
            );
        }

        // Validate IBAN format if provided
        if (! empty($data['IBAN']) && ! $this->isValidIban($data['IBAN'])) {
            throw ConnectionException::invalidConfiguration(
                'Invalid IBAN format provided'
            );
        }
    }

    /**
     * Basic IBAN validation.
     */
    protected function isValidIban(string $iban): bool
    {
        // Remove spaces and convert to uppercase
        $iban = strtoupper(str_replace(' ', '', $iban));

        // IBAN must be between 15 and 34 characters
        return preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban) === 1;
    }
}
