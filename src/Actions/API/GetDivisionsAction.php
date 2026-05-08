<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Division;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Exceptions\ApiException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class GetDivisionsAction
{
    use HandlesExactConnection;

    /**
     * Retrieve all divisions available to this connection from Exact Online.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array{
     *     filter?: string|null,
     *     select?: array<string>|null,
     *     top?: int|null,
     * }  $options  OData query options
     * @return Collection<int, array<string, mixed>>
     *
     * @throws ApiException
     */
    public function execute(ExactConnection $connection, array $options = []): Collection
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $division = new Division($picqerConnection);

            // Apply query options
            if (! empty($options['filter'])) {
                $division->filter($options['filter']);
            }

            if (! empty($options['select'])) {
                $division->select($options['select']);
            }

            if (! empty($options['top'])) {
                $division->top($options['top']);
            }

            // Get divisions
            $divisions = $division->get();

            $this->completeRequest($connection, $picqerConnection);

            if (config('exactonline-laravel-api.logging.debug', false)) {
                Log::info('Retrieved divisions from Exact Online', [
                    'connection_id' => $connection->id,
                    'count' => count($divisions),
                ]);
            }

            return collect($divisions)->map(fn ($division) => $division->attributes());

        } catch (\Exception $e) {
            Log::error('Failed to retrieve divisions from Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw ApiException::fromPicqerException($e, 'Division', (string) $connection->id);
        }
    }
}
