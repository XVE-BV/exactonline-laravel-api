<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\BankAccount;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

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

            $queryOptions = $this->buildQueryOptions($options);

            $bankAccounts = $bankAccount->get($queryOptions);

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
     * Build OData query options for Picqer's request API.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function buildQueryOptions(array $options): array
    {
        $queryOptions = [];

        if (! empty($options['filter'])) {
            $queryOptions['$filter'] = $options['filter'];
        }

        if (! empty($options['select']) && is_array($options['select'])) {
            $queryOptions['$select'] = implode(',', $options['select']);
        }

        if (! empty($options['expand']) && is_array($options['expand'])) {
            $queryOptions['$expand'] = implode(',', $options['expand']);
        }

        if (! empty($options['orderby'])) {
            $queryOptions['$orderby'] = $options['orderby'];
        }

        if (isset($options['top'])) {
            $queryOptions['$top'] = (int) $options['top'];
        }

        if (isset($options['skip'])) {
            $queryOptions['$skip'] = (int) $options['skip'];
        }

        return $queryOptions;
    }
}
