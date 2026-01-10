<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Contracts\HasExactMapping;
use Skylence\ExactonlineLaravelApi\Events\SalesInvoiceSynced;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;
use Skylence\ExactonlineLaravelApi\Support\Results\SyncResult;

/**
 * Sync a local model to Exact Online as a Sales Invoice.
 *
 * Note: Sales invoices in Exact Online are typically created from
 * sales orders and cannot be updated after creation. This action
 * only supports creation.
 */
class SyncSalesInvoiceAction
{
    /**
     * @param  Model&HasExactMapping  $model
     * @param  array<string, mixed>  $data
     */
    public function execute(
        ExactConnection $connection,
        Model&HasExactMapping $model,
        array $data,
        string $referenceType = 'sales_invoice'
    ): SyncResult {
        try {
            $existingId = $model->getExactId($connection, $referenceType);

            if ($existingId) {
                // Sales invoices cannot be updated, return existing
                Log::info('Sales invoice already exists in Exact', [
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
            Log::error('Failed to sync sales invoice to Exact Online', [
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
     * @param  Model&HasExactMapping  $model
     * @param  array<string, mixed>  $data
     */
    protected function create(
        ExactConnection $connection,
        Model&HasExactMapping $model,
        array $data,
        string $referenceType
    ): SyncResult {
        $createAction = Config::getAction('create_sales_invoice', CreateSalesInvoiceAction::class);

        $response = $createAction->execute($connection, $data);

        $exactId = $response['InvoiceID'] ?? $response['ID'] ?? null;
        $exactCode = $response['InvoiceNumber'] ?? null;

        if (! $exactId) {
            return SyncResult::failed('No InvoiceID returned from Exact Online');
        }

        $model->setExactId($connection, $exactId, $referenceType, $exactCode);

        Log::info('Created sales invoice mapping', [
            'connection_id' => $connection->id,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'exact_id' => $exactId,
            'invoice_number' => $exactCode,
        ]);

        $result = SyncResult::created($exactId, $exactCode);

        SalesInvoiceSynced::dispatch($connection, $model, $result);

        return $result;
    }
}
