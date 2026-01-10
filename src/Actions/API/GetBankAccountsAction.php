<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\BankAccount;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetBankAccountsAction
{
    use HandlesExactConnection;

    /**
     * Retrieve bank accounts from Exact Online.
     *
     * @param  array<string, mixed>  $options  OData query options
     * @return Collection<int, array<string, mixed>>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $options = []): Collection
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $bankAccount = new BankAccount($picqerConnection);

            $this->applyQueryOptions($bankAccount, $options);

            $bankAccounts = $bankAccount->get();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Retrieved bank accounts from Exact Online', [
                'connection_id' => $connection->id,
                'count' => count($bankAccounts),
            ]);

            return collect($bankAccounts)->map(fn ($b) => $b->attributes());

        } catch (\Exception $e) {
            Log::error('Failed to retrieve bank accounts from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve bank accounts: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function applyQueryOptions(BankAccount $entity, array $options): void
    {
        if (! empty($options['filter'])) {
            $entity->filter($options['filter']);
        }
        if (! empty($options['select'])) {
            $entity->select($options['select']);
        }
        if (! empty($options['top'])) {
            $entity->top($options['top']);
        }
    }
}
