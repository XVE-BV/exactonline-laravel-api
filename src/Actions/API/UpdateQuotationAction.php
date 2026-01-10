<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Quotation;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class UpdateQuotationAction
{
    use HandlesExactConnection;

    /**
     * Update an existing quotation in Exact Online.
     *
     * @param  string  $quotationId  The Exact Online quotation ID (GUID)
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $quotationId, array $data): array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $quotation = new Quotation($picqerConnection);
            $quotation->QuotationID = $quotationId;

            foreach ($data as $key => $value) {
                $quotation->{$key} = $value;
            }

            $quotation->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Updated quotation in Exact Online', [
                'connection_id' => $connection->id,
                'quotation_id' => $quotationId,
            ]);

            return $quotation->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to update quotation in Exact Online', [
                'connection_id' => $connection->id,
                'quotation_id' => $quotationId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to update quotation: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
