<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Employee;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreateEmployeeAction
{
    use HandlesExactConnection;

    /**
     * Create a new employee in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array{
     *     FirstName: string,
     *     LastName: string,
     *     FullName?: string|null,
     *     Code?: string|null,
     *     Email?: string|null,
     *     Phone?: string|null,
     *     Mobile?: string|null,
     *     Gender?: string|null,
     *     Title?: string|null,
     *     Initials?: string|null,
     *     MiddleName?: string|null,
     *     Nationality?: string|null,
     *     Language?: string|null,
     *     Notes?: string|null,
     *     City?: string|null,
     *     Country?: string|null,
     *     AddressLine1?: string|null,
     *     AddressLine2?: string|null,
     *     Postcode?: string|null,
     *     State?: string|null,
     *     StartDate?: string|null,
     *     EndDate?: string|null,
     *     BirthDate?: string|null,
     *     JobTitleDescription?: string|null,
     *     Division?: int|null,
     *     Manager?: string|null,
     *     CostCenter?: string|null,
     *     CostUnit?: string|null
     * }  $data  Employee data following Exact Online's schema
     * @return array<string, mixed> The created employee data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateEmployeeData($data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $employee = new Employee($picqerConnection);

            foreach ($data as $key => $value) {
                $employee->{$key} = $value;
            }

            $employee->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created employee in Exact Online', [
                'connection_id' => $connection->id,
                'employee_id' => $employee->ID,
                'employee_name' => $employee->FullName ?? ($employee->FirstName.' '.$employee->LastName),
            ]);

            return $employee->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create employee in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new ConnectionException(
                'Failed to create employee: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Validate required employee data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateEmployeeData(array $data): void
    {
        if (empty($data['FirstName'])) {
            throw ConnectionException::invalidConfiguration(
                'FirstName is required for employees'
            );
        }

        if (empty($data['LastName'])) {
            throw ConnectionException::invalidConfiguration(
                'LastName is required for employees'
            );
        }

        if (! empty($data['Email']) && ! filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
            throw ConnectionException::invalidConfiguration(
                'Invalid email format provided'
            );
        }
    }
}
