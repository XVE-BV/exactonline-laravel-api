<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Employee;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetEmployeeAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single employee from Exact Online.
     *
     * @param  string  $employeeId  The Exact Online employee ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $employeeId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $employee = new Employee($picqerConnection);

            $result = $employee->find($employeeId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved employee from Exact Online', [
                'connection_id' => $connection->id,
                'employee_id' => $employeeId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve employee from Exact Online', [
                'connection_id' => $connection->id,
                'employee_id' => $employeeId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve employee: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
