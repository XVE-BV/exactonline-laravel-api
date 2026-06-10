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

            $queryOptions = $this->buildQueryOptions($options);

            // Get divisions
            $divisions = $division->get($queryOptions);

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

    /**
     * Build OData query options for Picqer's request API.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function buildQueryOptions(array $options): array
    {
        $queryOptions = [];

        if (! empty($options['filter'])) {
            $queryOptions['$filter'] = $options['filter'];
        }

        if (! empty($options['select']) && is_array($options['select'])) {
            $queryOptions['$select'] = implode(',', $options['select']);
        }

        if (! empty($options['expand']) && is_array($options['expand'])) {
            $queryOptions['$expand'] = implode(',', $options['expand']);
        }

        if (! empty($options['orderby'])) {
            $queryOptions['$orderby'] = $options['orderby'];
        }

        if (isset($options['top'])) {
            $queryOptions['$top'] = (int) $options['top'];
        }

        if (isset($options['skip'])) {
            $queryOptions['$skip'] = (int) $options['skip'];
        }

        return $queryOptions;
    }
}
