<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use XVE\ExactonlineLaravelApi\Contracts\HasExactMapping;
use XVE\ExactonlineLaravelApi\Events\AddressSynced;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;
use XVE\ExactonlineLaravelApi\Support\Config;
use XVE\ExactonlineLaravelApi\Support\Results\SyncResult;

/**
 * Sync a local model to Exact Online as an Address.
 */
class SyncAddressAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(
        ExactConnection $connection,
        Model&HasExactMapping $model,
        array $data,
        string $referenceType = 'address'
    ): SyncResult {
        try {
            $existingId = $model->getExactId($connection, $referenceType);

            if ($existingId) {
                return $this->update($connection, $model, $existingId, $data, $referenceType);
            }

            return $this->create($connection, $model, $data, $referenceType);
        } catch (\Exception $e) {
            Log::error('Failed to sync address to Exact Online', [
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
     * @param  array<string, mixed>  $data
     */
    protected function create(
        ExactConnection $connection,
        Model&HasExactMapping $model,
        array $data,
        string $referenceType
    ): SyncResult {
        $createAction = Config::getAction('create_address', CreateAddressAction::class);

        $response = $createAction->execute($connection, $data);

        $exactId = $response['ID'] ?? null;
        $exactCode = $response['AddressLine1'] ?? null;

        if (! $exactId) {
            return SyncResult::failed('No ID returned from Exact Online');
        }

        $model->setExactId($connection, $exactId, $referenceType, $exactCode);

        Log::info('Created address mapping', [
            'connection_id' => $connection->id,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'exact_id' => $exactId,
        ]);

        $result = SyncResult::created($exactId, $exactCode);

        AddressSynced::dispatch($connection, $model, $result);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function update(
        ExactConnection $connection,
        Model&HasExactMapping $model,
        string $exactId,
        array $data,
        string $referenceType
    ): SyncResult {
        $updateAction = Config::getAction('update_address', UpdateAddressAction::class);

        $response = $updateAction->execute($connection, $exactId, $data);

        $exactCode = $response['AddressLine1'] ?? $model->getExactCode($connection, $referenceType);

        $model->setExactId($connection, $exactId, $referenceType, $exactCode);

        Log::info('Updated address in Exact', [
            'connection_id' => $connection->id,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'exact_id' => $exactId,
        ]);

        $result = SyncResult::updated($exactId, $exactCode);

        AddressSynced::dispatch($connection, $model, $result);

        return $result;
    }
}
