<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\VATCode;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreateVATCodeAction
{
    use HandlesExactConnection;

    /**
     * Create a new VAT code in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array{
     *     Code: string,
     *     Description: string,
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
     * }  $data  VAT code data following Exact Online's schema
     * @return array<string, mixed> The created VAT code data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateVATCodeData($data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $vatCode = new VATCode($picqerConnection);

            foreach ($data as $key => $value) {
                $vatCode->{$key} = $value;
            }

            $vatCode->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created VAT code in Exact Online', [
                'connection_id' => $connection->id,
                'vat_code_id' => $vatCode->ID,
                'code' => $vatCode->Code,
                'description' => $vatCode->Description,
            ]);

            return $vatCode->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create VAT code in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new ConnectionException(
                'Failed to create VAT code: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Validate required VAT code data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateVATCodeData(array $data): void
    {
        if (empty($data['Code'])) {
            throw ConnectionException::invalidConfiguration(
                'VAT code Code is required'
            );
        }

        if (empty($data['Description'])) {
            throw ConnectionException::invalidConfiguration(
                'VAT code Description is required'
            );
        }
    }
}
