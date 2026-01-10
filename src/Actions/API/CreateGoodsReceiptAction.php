<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\GoodsReceipt;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreateGoodsReceiptAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Create a new goods receipt in Exact Online.
     *
     * @param  array{
     *     PurchaseOrderID?: string|null,
     *     ReceiptDate?: string|null,
     *     Description?: string|null,
     *     Remarks?: string|null,
     *     Warehouse?: string|null,
     *     GoodsReceiptLines?: array<int, array{
     *         Item?: string|null,
     *         PurchaseOrderLineID?: string|null,
     *         Description?: string|null,
     *         QuantityReceived?: float|null,
     *         BatchNumbers?: array<int, array{
     *             BatchNumber?: string|null,
     *             Quantity?: float|null
     *         }>|null,
     *         SerialNumbers?: array<int, array{
     *             SerialNumber?: string|null
     *         }>|null
     *     }>|null
     * }  $data
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateCreatePayload('GoodsReceipt', $data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $goodsReceipt = new GoodsReceipt($picqerConnection);

            foreach ($data as $key => $value) {
                $goodsReceipt->{$key} = $value;
            }

            $goodsReceipt->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created goods receipt in Exact Online', [
                'connection_id' => $connection->id,
                'receipt_id' => $goodsReceipt->GoodsReceiptID,
                'receipt_number' => $goodsReceipt->ReceiptNumber ?? null,
            ]);

            return $goodsReceipt->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create goods receipt in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to create goods receipt: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
