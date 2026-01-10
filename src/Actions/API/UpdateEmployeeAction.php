<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Employee;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class UpdateEmployeeAction
{
    use HandlesExactConnection;

    /**
     * Update an existing employee in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $employeeId  The Exact Online employee ID (GUID)
     * @param  array<string, mixed>  $data  Employee data to update
     * @return array<string, mixed> The updated employee data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $employeeId, array $data): array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $employee = new Employee($picqerConnection);
            $employee->ID = $employeeId;

            foreach ($data as $key => $value) {
                $employee->{$key} = $value;
            }

            $employee->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Updated employee in Exact Online', [
                'connection_id' => $connection->id,
                'employee_id' => $employeeId,
            ]);

            return $employee->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to update employee in Exact Online', [
                'connection_id' => $connection->id,
                'employee_id' => $employeeId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to update employee: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
