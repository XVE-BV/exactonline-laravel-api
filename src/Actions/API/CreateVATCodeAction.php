<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\VatCode;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class CreateVATCodeAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Create a new VAT code in Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array<string, mixed>  $data  The entity data
     * @return array<string, mixed> The created VAT code data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateCreatePayload('VATCode', $data);
        $this->validateVATCodeData($data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $vatCode = new VatCode($picqerConnection);

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
