<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Project;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreateProjectAction
{
    use HandlesExactConnection;

    /**
     * Create a new project in Exact Online.
     *
     * @param  array{
     *     Code: string,
     *     Description: string,
     *     Account?: string|null,
     *     Type?: int|null,
     *     StartDate?: string|null,
     *     EndDate?: string|null,
     *     Manager?: string|null,
     *     Classification?: string|null,
     *     BudgetedAmount?: float|null,
     *     BudgetedCosts?: float|null,
     *     BudgetedRevenue?: float|null,
     *     BudgetedHoursPerHourType?: array|null,
     *     Notes?: string|null,
     *     SalesTimeQuantity?: float|null,
     *     SourceQuotation?: string|null,
     *     TimeQuantityToAlert?: float|null,
     *     UseBillingMilestones?: bool|null
     * }  $data
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateData($data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $project = new Project($picqerConnection);

            foreach ($data as $key => $value) {
                $project->{$key} = $value;
            }

            $project->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created project in Exact Online', [
                'connection_id' => $connection->id,
                'project_id' => $project->ID,
                'project_code' => $project->Code,
            ]);

            return $project->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create project in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to create project: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateData(array $data): void
    {
        if (empty($data['Code'])) {
            throw ConnectionException::invalidConfiguration(
                'Project code is required'
            );
        }

        if (empty($data['Description'])) {
            throw ConnectionException::invalidConfiguration(
                'Project description is required'
            );
        }
    }
}
