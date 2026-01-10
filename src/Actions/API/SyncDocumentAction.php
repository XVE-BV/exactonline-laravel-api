<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Contracts\HasExactMapping;
use Skylence\ExactonlineLaravelApi\Events\DocumentSynced;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;
use Skylence\ExactonlineLaravelApi\Support\Results\SyncResult;

/**
 * Sync a local model to Exact Online as a Document.
 * Creates based on existing mapping. Documents in Exact Online are immutable
 * and cannot be updated, so this action only creates new documents.
 */
class SyncDocumentAction
{
    /**
     * Sync a model to Exact Online.
     *
     * @param  Model&HasExactMapping  $model  The local model to sync
     * @param  array<string, mixed>  $data  The document data
     * @param  string  $referenceType  The mapping reference type
     */
    public function execute(
        ExactConnection $connection,
        Model&HasExactMapping $model,
        array $data,
        string $referenceType = 'primary'
    ): SyncResult {
        try {
            $existingId = $model->getExactId($connection, $referenceType);

            if ($existingId) {
                // Documents cannot be updated in Exact Online, return existing mapping
                Log::info('Document already exists in Exact Online, skipping sync', [
                    'connection_id' => $connection->id,
                    'model_type' => get_class($model),
                    'model_id' => $model->getKey(),
                    'exact_id' => $existingId,
                ]);

                $existingCode = $model->getExactCode($connection, $referenceType);
                $result = SyncResult::updated($existingId, $existingCode);

                DocumentSynced::dispatch($connection, $model, $result);

                return $result;
            }

            return $this->create($connection, $model, $data, $referenceType);
        } catch (\Exception $e) {
            Log::error('Failed to sync document to Exact Online', [
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
     * Create a new document in Exact.
     *
     * @param  array<string, mixed>  $data
     */
    protected function create(
        ExactConnection $connection,
        Model&HasExactMapping $model,
        array $data,
        string $referenceType
    ): SyncResult {
        $createAction = Config::getAction('create_document', CreateDocumentAction::class);

        $response = $createAction->execute($connection, $data);

        $exactId = $response['ID'] ?? null;
        $exactCode = $response['HID'] ?? null;

        if (! $exactId) {
            return SyncResult::failed('No ID returned from Exact Online');
        }

        // Store the mapping
        $model->setExactId($connection, $exactId, $referenceType, $exactCode);

        Log::info('Created document mapping', [
            'connection_id' => $connection->id,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'exact_id' => $exactId,
        ]);

        $result = SyncResult::created($exactId, $exactCode);

        DocumentSynced::dispatch($connection, $model, $result);

        return $result;
    }
}
