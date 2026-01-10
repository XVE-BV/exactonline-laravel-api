<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\GoodsReceipt;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetGoodsReceiptAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single goods receipt from Exact Online.
     *
     * @param  string  $receiptId  The Exact Online goods receipt ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $receiptId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $goodsReceipt = new GoodsReceipt($picqerConnection);

            $result = $goodsReceipt->find($receiptId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved goods receipt from Exact Online', [
                'connection_id' => $connection->id,
                'receipt_id' => $receiptId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve goods receipt from Exact Online', [
                'connection_id' => $connection->id,
                'receipt_id' => $receiptId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve goods receipt: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
