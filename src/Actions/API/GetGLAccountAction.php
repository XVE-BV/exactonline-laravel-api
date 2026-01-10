<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\GLAccount;
use Skylence\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class GetGLAccountAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single GL account from Exact Online.
     *
     * @param  string  $glAccountId  The Exact Online GL account ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $glAccountId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $glAccount = new GLAccount($picqerConnection);

            $result = $glAccount->find($glAccountId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved GL account from Exact Online', [
                'connection_id' => $connection->id,
                'gl_account_id' => $glAccountId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve GL account from Exact Online', [
                'connection_id' => $connection->id,
                'gl_account_id' => $glAccountId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve GL account: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
