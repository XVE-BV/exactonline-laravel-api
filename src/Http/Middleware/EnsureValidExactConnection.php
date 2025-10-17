<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidExactConnection
{
    /**
     * Handle an incoming request.
     *
     * This middleware ensures that:
     * 1. An active Exact Online connection exists
     * 2. The connection has valid tokens
     * 3. Tokens are refreshed if needed
     * 4. The connection is attached to the request for use in controllers
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $connectionId = null): Response
    {
        // Get the connection
        $connection = $this->resolveConnection($request, $connectionId);

        if ($connection === null) {
            throw ConnectionException::connectionNotFound();
        }

        // Check if connection is active
        if (! $connection->is_active) {
            throw ConnectionException::connectionInactive($connection->id);
        }

        // Check and refresh token if needed
        $this->ensureValidToken($connection);

        // Attach connection to request for use in controllers
        $request->attributes->set('exactConnection', $connection);
        $request->setUserResolver(function () use ($connection) {
            return $connection;
        });

        // Update last used timestamp
        $connection->update(['last_used_at' => now()]);

        return $next($request);
    }

    /**
     * Resolve the Exact Online connection
     */
    protected function resolveConnection(Request $request, ?string $connectionId = null): ?ExactConnection
    {
        // Priority 1: Explicit connection ID passed to middleware
        if ($connectionId !== null) {
            return ExactConnection::find($connectionId);
        }

        // Priority 2: Connection ID from request (route parameter or query)
        $requestConnectionId = $request->route('connection_id')
            ?? $request->query('connection_id')
            ?? $request->input('connection_id');

        if ($requestConnectionId !== null) {
            return ExactConnection::find($requestConnectionId);
        }

        // Priority 3: Connection for authenticated user
        if ($request->user() !== null) {
            $userId = $request->user()->getAuthIdentifier();

            return ExactConnection::where('user_id', $userId)
                ->where('is_active', true)
                ->orderBy('last_used_at', 'desc')
                ->first();
        }

        // Priority 4: Default active connection (single-tenant applications)
        return ExactConnection::where('is_active', true)
            ->orderBy('last_used_at', 'desc')
            ->first();
    }

    /**
     * Ensure the connection has a valid access token
     *
     *
     * @throws ConnectionException
     */
    protected function ensureValidToken(ExactConnection $connection): void
    {
        // Check if we have tokens
        if (empty($connection->access_token) || empty($connection->refresh_token)) {
            throw ConnectionException::tokensNotFound($connection->id);
        }

        // Check if token needs refresh (proactive at 9 minutes)
        if ($this->tokenNeedsRefresh($connection)) {
            try {
                $refreshAction = Config::getAction(
                    'refresh_access_token',
                    RefreshAccessTokenAction::class
                );

                $refreshAction->execute($connection);

                // Refresh the connection to get updated tokens
                $connection->refresh();

            } catch (\Exception $e) {
                throw ConnectionException::tokenRefreshFailed($connection->id, $e->getMessage());
            }
        }
    }

    /**
     * Check if token needs refresh (proactive at 9 minutes)
     */
    protected function tokenNeedsRefresh(ExactConnection $connection): bool
    {
        if (empty($connection->token_expires_at)) {
            return true;
        }

        // Refresh proactively at 9 minutes (540 seconds before expiry)
        return $connection->token_expires_at < (now()->timestamp + 540);
    }
}
