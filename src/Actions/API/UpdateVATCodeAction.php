<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\VATCode;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class UpdateVATCodeAction
{
    use HandlesExactConnection;

    /**
     * Update an existing VAT code in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $vatCodeId  The VAT code ID (GUID) to update
     * @param  array{
     *     Code?: string,
     *     Description?: string,
     *     Account?: string|null,
     *     CalculationBasis?: string|null,
     *     Charged?: string|null,
     *     Country?: string|null,
     *     GLDiscountPurchase?: string|null,
     *     GLDiscountSales?: string|null,
     *     GLToClaim?: string|null,
     *     GLToPay?: string|null,
     *     IntraStat?: bool|null,
     *     IsBlocked?: bool|null,
     *     LegalText?: string|null,
     *     Percentage?: float|null,
     *     TaxReturnType?: int|null,
     *     Type?: string|null,
     *     VATDocType?: string|null,
     *     VATMargin?: int|null,
     *     VATPartialRatio?: int|null,
     *     VATTransactionType?: string|null
     * }  $data  VAT code data to update
     * @return array<string, mixed> The updated VAT code data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $vatCodeId, array $data): array
    {
        $this->validateUpdateData($data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $vatCode = new VATCode($picqerConnection);

            $existingVATCode = $vatCode->find($vatCodeId);

            if ($existingVATCode === null) {
                throw new ConnectionException("VAT code with ID {$vatCodeId} not found");
            }

            foreach ($data as $key => $value) {
                $existingVATCode->{$key} = $value;
            }

            $existingVATCode->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Updated VAT code in Exact Online', [
                'connection_id' => $connection->id,
                'vat_code_id' => $vatCodeId,
                'updated_fields' => array_keys($data),
            ]);

            return $existingVATCode->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to update VAT code in Exact Online', [
                'connection_id' => $connection->id,
                'vat_code_id' => $vatCodeId,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new ConnectionException(
                'Failed to update VAT code: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Validate update data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateUpdateData(array $data): void
    {
        if (empty($data)) {
            throw ConnectionException::invalidConfiguration(
                'No data provided for VAT code update'
            );
        }
    }
}
