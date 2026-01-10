<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Contracts\HasExactMapping;
use Skylence\ExactonlineLaravelApi\Events\BankAccountSynced;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;
use Skylence\ExactonlineLaravelApi\Support\Results\SyncResult;

/**
 * Sync a local model to Exact Online as a BankAccount.
 * Creates or updates based on existing mapping.
 */
class SyncBankAccountAction
{
    /**
     * Sync a model to Exact Online as a BankAccount.
     *
     * @param  Model&HasExactMapping  $model  The local model to sync
     * @param  array<string, mixed>  $data  The bank account data (must include Account)
     * @param  string  $referenceType  The mapping reference type
     */
    public function execute(
        ExactConnection $connection,
        Model&HasExactMapping $model,
        array $data,
        string $referenceType = 'bank_account'
    ): SyncResult {
        try {
            $existingId = $model->getExactId($connection, $referenceType);

            if ($existingId) {
                return $this->update($connection, $model, $existingId, $data, $referenceType);
            }

            return $this->create($connection, $model, $data, $referenceType);
        } catch (\Exception $e) {
            Log::error('Failed to sync bank account to Exact Online', [
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
     * Create a new bank account in Exact.
     *
     * @param  array<string, mixed>  $data
     */
    protected function create(
        ExactConnection $connection,
        Model&HasExactMapping $model,
        array $data,
        string $referenceType
    ): SyncResult {
        $createAction = Config::getAction('create_bank_account', CreateBankAccountAction::class);

        $response = $createAction->execute($connection, $data);

        $exactId = $response['ID'] ?? null;

        if (! $exactId) {
            return SyncResult::failed('No ID returned from Exact Online');
        }

        $model->setExactId($connection, $exactId, $referenceType);

        Log::info('Created bank account mapping', [
            'connection_id' => $connection->id,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'exact_id' => $exactId,
        ]);

        $result = SyncResult::created($exactId);

        BankAccountSynced::dispatch($connection, $model, $result);

        return $result;
    }

    /**
     * Update an existing bank account in Exact.
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
        $updateAction = Config::getAction('update_bank_account', UpdateBankAccountAction::class);

        $updateAction->execute($connection, $exactId, $data);

        $model->setExactId($connection, $exactId, $referenceType);

        Log::info('Updated bank account in Exact', [
            'connection_id' => $connection->id,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'exact_id' => $exactId,
        ]);

        $result = SyncResult::updated($exactId);

        BankAccountSynced::dispatch($connection, $model, $result);

        return $result;
    }
}
