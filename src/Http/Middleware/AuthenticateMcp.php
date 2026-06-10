<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates the static bearer token for the MCP HTTP endpoint.
 *
 * Fails closed: a missing or blank token in config denies every request,
 * preventing accidental open exposure when the env variable is not set.
 *
 * Token is read via config() (not env() directly) so config:cache works.
 */
class AuthenticateMcp
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('exactonline-laravel-api.mcp.auth.token');
        $header = config('exactonline-laravel-api.mcp.auth.header', 'X-MCP-Token');

        // Fail closed: if the server token is not configured as a string, deny everything.
        if (! is_string($expected) || $expected === '') {
            return $this->deny($request);
        }

        if (! is_string($header) || $header === '') {
            return $this->deny($request);
        }

        // Accept token from the configured header or from the Authorization bearer.
        $provided = $request->header($header) ?? $request->bearerToken();

        if (! is_string($provided) || $provided === '') {
            return $this->deny($request);
        }

        // Constant-time comparison to prevent timing-based oracle attacks.
        if (! hash_equals($expected, $provided)) {
            return $this->deny($request);
        }

        return $next($request);
    }

    private function deny(Request $request): Response
    {
        // Return a JSON-RPC-shaped error body so MCP clients get a parseable response.
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => null,
            'error' => [
                'code' => -32001,
                'message' => 'Unauthorized.',
            ],
        ], Response::HTTP_UNAUTHORIZED);
    }
}
