<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Services;

use Illuminate\Support\Collection;
use Picqer\Financials\Exact\Connection;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

/**
 * Low-level HTTP service for Exact Online API calls.
 * Wraps the picqer/exact-php-client library.
 */
class ExactApiService
{
    /**
     * Cache of Picqer connections.
     *
     * @var array<int, Connection>
     */
    protected array $connections = [];

    /**
     * Perform a GET request to the Exact API.
     *
     * @param  array<string, mixed>  $params  OData query parameters
     * @return Collection<int, array<string, mixed>>
     */
    public function get(ExactConnection $connection, string $endpoint, array $params = []): Collection
    {
        $picqerConnection = $this->getConnection($connection);

        $url = $this->buildUrl($endpoint, $params);
        $response = $picqerConnection->get($url);

        return collect($response);
    }

    /**
     * Perform a GET request for a single entity.
     *
     * @return array<string, mixed>|null
     */
    public function find(ExactConnection $connection, string $endpoint, string $id): ?array
    {
        $picqerConnection = $this->getConnection($connection);

        $url = "{$endpoint}(guid'{$id}')";
        $response = $picqerConnection->get($url);

        return $response[0] ?? null;
    }

    /**
     * Perform a POST request to create an entity.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function post(ExactConnection $connection, string $endpoint, array $data): array
    {
        $picqerConnection = $this->getConnection($connection);

        return $picqerConnection->post($endpoint, $data);
    }

    /**
     * Perform a PUT request to update an entity.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function put(ExactConnection $connection, string $endpoint, string $id, array $data): array
    {
        $picqerConnection = $this->getConnection($connection);

        $url = "{$endpoint}(guid'{$id}')";

        return $picqerConnection->put($url, $data);
    }

    /**
     * Perform a DELETE request.
     */
    public function delete(ExactConnection $connection, string $endpoint, string $id): bool
    {
        $picqerConnection = $this->getConnection($connection);

        $url = "{$endpoint}(guid'{$id}')";
        $picqerConnection->delete($url);

        return true;
    }

    /**
     * Perform a raw GET request returning the full response.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function rawGet(ExactConnection $connection, string $url, array $params = []): array
    {
        $picqerConnection = $this->getConnection($connection);

        $fullUrl = $this->buildUrl($url, $params);

        return $picqerConnection->get($fullUrl);
    }

    /**
     * Get the Picqer Connection instance for an ExactConnection.
     *
     * @throws ConnectionException
     */
    public function getConnection(ExactConnection $connection): Connection
    {
        if (! isset($this->connections[$connection->id])) {
            $this->connections[$connection->id] = $this->createConnection($connection);
        }

        return $this->connections[$connection->id];
    }

    /**
     * Create a new Picqer Connection instance.
     *
     * @throws ConnectionException
     */
    protected function createConnection(ExactConnection $connection): Connection
    {
        if (! $connection->is_active) {
            throw ConnectionException::inactive($connection);
        }

        if (! $connection->access_token) {
            throw ConnectionException::noAccessToken($connection);
        }

        return $connection->getPicqerConnection();
    }

    /**
     * Clear the connection cache.
     */
    public function clearConnectionCache(?ExactConnection $connection = null): void
    {
        if ($connection) {
            unset($this->connections[$connection->id]);
        } else {
            $this->connections = [];
        }
    }

    /**
     * Refresh the cached connection with updated tokens.
     */
    public function refreshConnectionCache(ExactConnection $connection): void
    {
        unset($this->connections[$connection->id]);
        $this->connections[$connection->id] = $this->createConnection($connection);
    }

    /**
     * Build URL with OData query parameters.
     *
     * @param  array<string, mixed>  $params
     */
    protected function buildUrl(string $endpoint, array $params = []): string
    {
        if (empty($params)) {
            return $endpoint;
        }

        $queryParams = [];

        foreach ($params as $key => $value) {
            if ($key === 'filter' || $key === '$filter') {
                $queryParams['$filter'] = $value;
            } elseif ($key === 'select' || $key === '$select') {
                $queryParams['$select'] = is_array($value) ? implode(',', $value) : $value;
            } elseif ($key === 'expand' || $key === '$expand') {
                $queryParams['$expand'] = is_array($value) ? implode(',', $value) : $value;
            } elseif ($key === 'orderby' || $key === '$orderby') {
                $queryParams['$orderby'] = $value;
            } elseif ($key === 'top' || $key === '$top') {
                $queryParams['$top'] = (int) $value;
            } elseif ($key === 'skip' || $key === '$skip') {
                $queryParams['$skip'] = (int) $value;
            } else {
                $queryParams[$key] = $value;
            }
        }

        return $endpoint.'?'.http_build_query($queryParams);
    }

    /**
     * Get the rate limit headers from the last response.
     *
     * @return array<string, mixed>
     */
    public function getLastRateLimitHeaders(ExactConnection $connection): array
    {
        $picqerConnection = $this->getConnection($connection);

        // Note: picqer client doesn't expose rate limit headers directly
        // This would need to be enhanced based on actual implementation
        return [];
    }
}
