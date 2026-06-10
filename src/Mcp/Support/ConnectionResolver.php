<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Mcp\Support;

use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

/**
 * Resolves an ExactConnection from MCP tool input parameters.
 *
 * Priority: connection_id > connection_name > division > latest active.
 * Throws a clear error rather than guessing when input is ambiguous.
 */
class ConnectionResolver
{
    /**
     * Resolve a connection from optional tool parameters.
     *
     * @param  array<string, mixed>  $params  Tool input; keys: connection_id, connection_name, division
     *
     * @throws ConnectionException when no connection matches or the result is ambiguous
     */
    public function resolve(array $params): ExactConnection
    {
        if (! empty($params['connection_id'])) {
            return $this->byId((string) $params['connection_id']);
        }

        if (! empty($params['connection_name'])) {
            return $this->byName((string) $params['connection_name'], $params['division'] ?? null);
        }

        if (! empty($params['division'])) {
            return $this->byDivision((string) $params['division']);
        }

        return $this->latestActive();
    }

    private function byId(string $id): ExactConnection
    {
        $connection = ExactConnection::find($id);

        if ($connection === null) {
            throw new ConnectionException("No connection found with id={$id}.");
        }

        return $connection;
    }

    private function byName(string $name, mixed $division): ExactConnection
    {
        $query = ExactConnection::where('name', $name);

        if (! empty($division)) {
            $query->where('division', (string) $division);
        }

        $results = $query->get();

        if ($results->isEmpty()) {
            throw new ConnectionException("No connection found with name=\"{$name}\".");
        }

        if ($results->count() > 1) {
            throw new ConnectionException(
                "Multiple connections named \"{$name}\" exist. Pass connection_id or add division to disambiguate."
            );
        }

        return $results->first();
    }

    private function byDivision(string $division): ExactConnection
    {
        $results = ExactConnection::where('division', $division)
            ->where('is_active', true)
            ->orderByDesc('last_used_at')
            ->get();

        if ($results->isEmpty()) {
            throw new ConnectionException("No active connection found for division={$division}.");
        }

        if ($results->count() > 1) {
            throw new ConnectionException(
                "Multiple active connections for division={$division}. Pass connection_id to disambiguate."
            );
        }

        return $results->first();
    }

    private function latestActive(): ExactConnection
    {
        $connection = ExactConnection::where('is_active', true)
            ->orderByDesc('last_used_at')
            ->first();

        if ($connection === null) {
            throw new ConnectionException(
                'No active Exact Online connection found. Pass connection_id or connection_name.'
            );
        }

        return $connection;
    }
}
