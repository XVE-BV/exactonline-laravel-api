<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\GLAccount;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class CreateGLAccountAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Create a new GL account in Exact Online.
     *
     * @param  array<string, mixed>  $data  The entity data
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
