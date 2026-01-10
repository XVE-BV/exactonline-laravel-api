<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Contracts\HasExactMapping;
use Skylence\ExactonlineLaravelApi\Events\EmployeeSynced;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;
use Skylence\ExactonlineLaravelApi\Support\Results\SyncResult;

/**
 * Sync a local model to Exact Online as an Employee.
 * Creates or updates based on existing mapping.
 */
class SyncEmployeeAction
{
    /**
     * Sync a model to Exact Online as an Employee.
     *
     * @param  Model&HasExactMapping  $model  The local model to sync
     * @param  array<string, mixed>  $data  The employee data (FirstName and LastName required)
     * @param  string  $referenceType  The mapping reference type
     */
    public function execute(
        ExactConnection $connection,
        Model&HasExactMapping $model,
        array $data,
        string $referenceType = 'employee'
    ): SyncResult {
        try {
            $existingId = $model->getExactId($connection, $referenceType);

            if ($existingId) {
                return $this->update($connection, $model, $existingId, $data, $referenceType);
            }

            return $this->create($connection, $model, $data, $referenceType);
        } catch (\Exception $e) {
            Log::error('Failed to sync employee to Exact Online', [
                'connection_id' => $connection->id,
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'error' => $e->getMessage(),
            ]);

            $model->recordExactError($connection, $e->getMessage(), $referenceType);

            return SyncResult::failed($e->getMessage());
        }
    }

    /**
     * Create a new employee in Exact.
     *
     * @param  array<string, mixed>  $data
     */
    protected function create(
        ExactConnection $connection,
        Model&HasExactMapping $model,
        array $data,
        string $referenceType
    ): SyncResult {
        $createAction = Config::getAction('create_employee', CreateEmployeeAction::class);

        $response = $createAction->execute($connection, $data);

        $exactId = $response['ID'] ?? null;

        if (! $exactId) {
            return SyncResult::failed('No ID returned from Exact Online');
        }

        $model->setExactId($connection, $exactId, $referenceType);

        Log::info('Created employee mapping', [
            'connection_id' => $connection->id,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'exact_id' => $exactId,
        ]);

        $result = SyncResult::created($exactId);

        EmployeeSynced::dispatch($connection, $model, $result);

        return $result;
    }

    /**
     * Update an existing employee in Exact.
     *
     * @param  array<string, mixed>  $data
     */
    protected function update(
        ExactConnection $connection,
        Model&HasExactMapping $model,
        string $exactId,
        array $data,
        string $referenceType
    ): SyncResult {
        $updateAction = Config::getAction('update_employee', UpdateEmployeeAction::class);

        $updateAction->execute($connection, $exactId, $data);

        $model->setExactId($connection, $exactId, $referenceType);

        Log::info('Updated employee in Exact', [
            'connection_id' => $connection->id,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'exact_id' => $exactId,
        ]);

        $result = SyncResult::updated($exactId);

        EmployeeSynced::dispatch($connection, $model, $result);

        return $result;
    }
}
