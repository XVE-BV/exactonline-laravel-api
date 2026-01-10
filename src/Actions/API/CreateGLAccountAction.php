<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\GLAccount;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class CreateGLAccountAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Create a new GL account in Exact Online.
     *
     * @param  array{
     *     Code: string,
     *     Description: string,
     *     Type?: int|null,
     *     TypeDescription?: string|null,
     *     AssimilatedVATBox?: int|null,
     *     BalanceSide?: string|null,
     *     BalanceType?: string|null,
     *     Compress?: bool|null,
     *     Costcenter?: string|null,
     *     Costunit?: string|null,
     *     ExcludeVATListing?: bool|null,
     *     IsBlocked?: bool|null,
     *     Matching?: bool|null,
     *     PrivateGLAccount?: string|null,
     *     PrivatePercentage?: float|null,
     *     ReportingCode?: string|null,
     *     SearchCode?: string|null,
     *     UseCostcenter?: int|null,
     *     UseCostunit?: int|null,
     *     VATCode?: string|null,
     *     VATDescription?: string|null,
     *     VATGLAccountType?: string|null,
     *     VATNonDeductibleGLAccount?: string|null,
     *     VATNonDeductiblePercentage?: float|null
     * }  $data
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        $this->validateCreatePayload('GLAccount', $data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $glAccount = new GLAccount($picqerConnection);

            foreach ($data as $key => $value) {
                $glAccount->{$key} = $value;
            }

            $glAccount->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Created GL account in Exact Online', [
                'connection_id' => $connection->id,
                'gl_account_id' => $glAccount->ID,
                'gl_account_code' => $glAccount->Code,
            ]);

            return $glAccount->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create GL account in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to create GL account: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
