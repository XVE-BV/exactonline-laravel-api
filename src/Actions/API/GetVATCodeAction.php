<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\VATCode;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetVATCodeAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single VAT code from Exact Online.
     *
     * @param  string  $vatCodeId  The Exact Online VAT code ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $vatCodeId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $vatCode = new VATCode($picqerConnection);

            $result = $vatCode->find($vatCodeId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved VAT code from Exact Online', [
                'connection_id' => $connection->id,
                'vat_code_id' => $vatCodeId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve VAT code from Exact Online', [
                'connection_id' => $connection->id,
                'vat_code_id' => $vatCodeId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve VAT code: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
