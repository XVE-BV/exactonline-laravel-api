<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\GLAccount;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class UpdateGLAccountAction
{
    use HandlesExactConnection;

    /**
     * Update an existing GL account in Exact Online.
     *
     * @param  string  $glAccountId  The Exact Online GL account ID (GUID)
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $glAccountId, array $data): array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $glAccount = new GLAccount($picqerConnection);
            $glAccount->ID = $glAccountId;

            foreach ($data as $key => $value) {
                $glAccount->{$key} = $value;
            }

            $glAccount->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Updated GL account in Exact Online', [
                'connection_id' => $connection->id,
                'gl_account_id' => $glAccountId,
            ]);

            return $glAccount->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to update GL account in Exact Online', [
                'connection_id' => $connection->id,
                'gl_account_id' => $glAccountId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to update GL account: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
