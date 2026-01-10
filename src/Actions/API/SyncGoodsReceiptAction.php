<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Contracts\HasExactMapping;
use Skylence\ExactonlineLaravelApi\Events\GoodsReceiptSynced;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;
use Skylence\ExactonlineLaravelApi\Support\Results\SyncResult;

/**
 * Sync a local model to Exact Online as a Goods Receipt.
 *
 * Note: Goods receipts in Exact Online cannot be updated after creation.
 * This action only supports creation.
 */
class SyncGoodsReceiptAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(
        ExactConnection $connection,
        Model&HasExactMapping $model,
        array $data,
        string $referenceType = 'goods_receipt'
    ): SyncResult {
        try {
            $existingId = $model->getExactId($connection, $referenceType);

            if ($existingId) {
                // Goods receipts cannot be updated, return existing
                Log::info('Goods receipt already exists in Exact', [
                    'connection_id' => $connection->id,
                    'model_type' => get_class($model),
                    'model_id' => $model->getKey(),
                    'exact_id' => $existingId,
                ]);

                return SyncResult::updated(
                    $existingId,
                    $model->getExactCode($connection, $referenceType)
                );
            }

            return $this->create($connection, $model, $data, $referenceType);
        } catch (\Exception $e) {
            Log::error('Failed to sync goods receipt to Exact Online', [
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
        $createAction = Config::getAction('create_goods_receipt', CreateGoodsReceiptAction::class);

        $response = $createAction->execute($connection, $data);

        $exactId = $response['GoodsReceiptID'] ?? null;
        $exactCode = $response['ReceiptNumber'] ?? null;

        if (! $exactId) {
            return SyncResult::failed('No GoodsReceiptID returned from Exact Online');
        }

        $model->setExactId($connection, $exactId, $referenceType, $exactCode);

        Log::info('Created goods receipt mapping', [
            'connection_id' => $connection->id,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'exact_id' => $exactId,
        ]);

        $result = SyncResult::created($exactId, $exactCode);

        GoodsReceiptSynced::dispatch($connection, $model, $result);

        return $result;
    }
}
