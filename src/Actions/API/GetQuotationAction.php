<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Quotation;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetQuotationAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single quotation from Exact Online.
     *
     * @param  string  $quotationId  The Exact Online quotation ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $quotationId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $quotation = new Quotation($picqerConnection);

            $result = $quotation->find($quotationId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved quotation from Exact Online', [
                'connection_id' => $connection->id,
                'quotation_id' => $quotationId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve quotation from Exact Online', [
                'connection_id' => $connection->id,
                'quotation_id' => $quotationId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve quotation: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
