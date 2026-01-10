<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Contracts\HasExactMapping;
use Skylence\ExactonlineLaravelApi\Events\GoodsDeliverySynced;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;
use Skylence\ExactonlineLaravelApi\Support\Results\SyncResult;

/**
 * Sync a local model to Exact Online as a Goods Delivery.
 */
class SyncGoodsDeliveryAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(
        ExactConnection $connection,
        Model&HasExactMapping $model,
        array $data,
        string $referenceType = 'goods_delivery'
    ): SyncResult {
        try {
            $existingId = $model->getExactId($connection, $referenceType);

            if ($existingId) {
                Log::info('Goods delivery already exists in Exact Online', [
                    'connection_id' => $connection->id,
                    'model_type' => get_class($model),
                    'model_id' => $model->getKey(),
                    'exact_id' => $existingId,
                ]);

                $existingCode = $model->getExactCode($connection, $referenceType);
                $result = SyncResult::skipped('Goods delivery already exists', $existingId, $existingCode);

                GoodsDeliverySynced::dispatch($connection, $model, $result);

                return $result;
            }

            return $this->create($connection, $model, $data, $referenceType);
        } catch (\Exception $e) {
            Log::error('Failed to sync goods delivery to Exact Online', [
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
        $createAction = Config::getAction('create_goods_delivery', CreateGoodsDeliveryAction::class);

        $response = $createAction->execute($connection, $data);

        $exactId = $response['EntryID'] ?? null;
        $exactCode = $response['DeliveryNumber'] ?? null;

        if (! $exactId) {
            return SyncResult::failed('No EntryID returned from Exact Online');
        }

        $model->setExactId($connection, $exactId, $referenceType, $exactCode);

        Log::info('Created goods delivery mapping', [
            'connection_id' => $connection->id,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'exact_id' => $exactId,
        ]);

        $result = SyncResult::created($exactId, $exactCode);

        GoodsDeliverySynced::dispatch($connection, $model, $result);

        return $result;
    }
}
