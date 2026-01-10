<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Employee;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetEmployeesAction
{
    use HandlesExactConnection;

    /**
     * Retrieve employees from Exact Online.
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
            $employee = new Employee($picqerConnection);

            $this->applyQueryOptions($employee, $options);

            $employees = $employee->get();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Retrieved employees from Exact Online', [
                'connection_id' => $connection->id,
                'count' => count($employees),
            ]);

            return collect($employees)->map(fn ($e) => $e->attributes());

        } catch (\Exception $e) {
            Log::error('Failed to retrieve employees from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve employees: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function applyQueryOptions(Employee $entity, array $options): void
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
