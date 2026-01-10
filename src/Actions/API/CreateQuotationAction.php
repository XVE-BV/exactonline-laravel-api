<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Quotation;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreateQuotationAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Create a new quotation in Exact Online.
     *
     * @param  array{
     *     OrderAccount: string,
     *     QuotationDate?: string|null,
     *     ClosingDate?: string|null,
     *     Description?: string|null,
     *     Currency?: string|null,
     *     YourRef?: string|null,
     *     Remarks?: string|null,
     *     DeliveryAccount?: string|null,
     *     InvoiceAccount?: string|null,
     *     Status?: int|null,
     *     QuotationLines?: array<int, array{
     *         Item: string,
     *         Description?: string|null,
     *         Quantity: float,
     *         UnitPrice?: float|null,
     *         Discount?: float|null,
     *         VATCode?: string|null
     *     }>|null
     * }  $data
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateCreatePayload('Quotation', $data);
        $this->validateData($data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $quotation = new Quotation($picqerConnection);

            foreach ($data as $key => $value) {
                $quotation->{$key} = $value;
            }

            $quotation->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created quotation in Exact Online', [
                'connection_id' => $connection->id,
                'quotation_id' => $quotation->QuotationID,
                'quotation_number' => $quotation->QuotationNumber ?? null,
            ]);

            return $quotation->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create quotation in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to create quotation: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateData(array $data): void
    {
        if (empty($data['OrderAccount'])) {
            throw ConnectionException::invalidConfiguration(
                'OrderAccount (Account ID) is required for quotations'
            );
        }
    }
}
