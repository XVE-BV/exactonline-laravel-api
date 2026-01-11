<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Http\Controllers\OAuth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Actions\API\SyncDivisionsAction;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\AcquireAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Exceptions\TokenRefreshException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class CallbackController extends Controller
{
    /**
     * Handle OAuth callback from Exact Online
     *
     * This controller receives the authorization code from Exact Online,
     * validates the state parameter for CSRF protection, and exchanges
     * the code for access and refresh tokens.
     */
    public function __invoke(Request $request): RedirectResponse|JsonResponse
    {
        $debug = (bool) config('exactonline-laravel-api.logging.debug', false);

        if ($debug) {
            Log::info('OAuth callback received', [
                'session_id' => $request->session()->getId(),
                'has_code' => $request->has('code'),
                'has_state' => $request->has('state'),
                'has_error' => $request->has('error'),
                'error' => $request->input('error'),
                'error_description' => $request->input('error_description'),
                'request_state' => $request->input('state') ? substr($request->input('state'), 0, 10).'...' : null,
                'session_state' => $request->session()->get('exact_oauth_state') ? substr($request->session()->get('exact_oauth_state'), 0, 10).'...' : null,
                'session_connection_id' => $request->session()->get('exact_oauth_connection_id'),
                'all_session_keys' => array_keys($request->session()->all()),
            ]);
        }

        try {
            // Validate state parameter for CSRF protection
            $this->validateState($request);

            if ($debug) {
                Log::debug('OAuth state validated successfully');
            }

            // Check for OAuth errors from Exact Online
            $this->checkForOAuthError($request);

            // Get authorization code
            $authorizationCode = $request->input('code');

            if (! $authorizationCode) {
                throw new \InvalidArgumentException('No authorization code received from Exact Online');
            }

            if ($debug) {
                Log::debug('OAuth authorization code received', [
                    'code_length' => strlen($authorizationCode),
                ]);
            }

            // Get the connection
            $connection = $this->getConnection($request);

            if ($debug) {
                Log::debug('OAuth connection retrieved for token exchange', [
                    'connection_id' => $connection->id,
                    'has_client_secret' => ! empty($connection->getDecryptedClientSecret()),
                ]);
            }

            // Exchange authorization code for tokens
            $action = Config::getAction(
                'acquire_access_token',
                AcquireAccessTokenAction::class
            );

            $tokens = $action->execute($connection, $authorizationCode);

            if ($debug) {
                Log::info('OAuth callback successful', [
                    'connection_id' => $connection->id,
                    'user_id' => $connection->user_id,
                    'token_expires_at' => $tokens['expires_at'],
                ]);
            }

            // Sync available divisions after successful authentication
            $this->syncDivisions($connection, $debug);

            // Get redirect URL before cleanup
            $redirectTo = $request->session()->get('exact_oauth_redirect_to');

            // Clean up session
            $this->cleanupSession($request);

            // Return success response
            return $this->handleSuccess($request, $connection, $redirectTo);

        } catch (\Throwable $e) {
            Log::error('OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => [
                    'has_code' => $request->has('code'),
                    'has_state' => $request->has('state'),
                    'has_error' => $request->has('error'),
                ],
            ]);

            // Get redirect URL before cleanup
            $redirectTo = $request->session()->get('exact_oauth_redirect_to');

            // Clean up session
            $this->cleanupSession($request);

            // Return failure response
            return $this->handleFailure($request, $e, $redirectTo);
        }
    }

    /**
     * Validate the state parameter for CSRF protection
     *
     * @throws \RuntimeException
     */
    protected function validateState(Request $request): void
    {
        $sessionState = $request->session()->get('exact_oauth_state');
        $requestState = $request->input('state');

        if (! $sessionState || ! $requestState) {
            throw new \RuntimeException('Missing state parameter - possible CSRF attack');
        }

        if (! hash_equals($sessionState, $requestState)) {
            throw new \RuntimeException('Invalid state parameter - possible CSRF attack');
        }
    }

    /**
     * Check for OAuth error response from Exact Online
     *
     * @throws \RuntimeException
     */
    protected function checkForOAuthError(Request $request): void
    {
        if ($request->has('error')) {
            $error = $request->input('error');
            $errorDescription = $request->input('error_description', 'Unknown error');

            // Common OAuth error codes
            $errorMessages = [
                'access_denied' => 'User denied access to Exact Online',
                'invalid_request' => 'Invalid OAuth request',
                'unauthorized_client' => 'Client not authorized for this grant type',
                'invalid_client' => 'Invalid client credentials',
                'invalid_grant' => 'Invalid or expired authorization code',
                'server_error' => 'Exact Online server error',
                'temporarily_unavailable' => 'Exact Online is temporarily unavailable',
            ];

            $message = $errorMessages[$error] ?? $errorDescription;

            throw new \RuntimeException("OAuth error: {$error} - {$message}");
        }
    }

    /**
     * Get the connection for this OAuth flow
     *
     * @throws \RuntimeException
     */
    protected function getConnection(Request $request): ExactConnection
    {
        // Get connection ID from session
        $connectionId = $request->session()->get('exact_oauth_connection_id');

        if (! $connectionId) {
            throw new \RuntimeException(
                'No connection ID found in session. '.
                'The OAuth flow may have expired or been initiated from another browser.'
            );
        }

        $connection = ExactConnection::find($connectionId);

        if (! ($connection instanceof ExactConnection)) {
            throw new \RuntimeException(
                'Connection not found. '.
                'The connection may have been deleted during the OAuth flow.'
            );
        }

        // Verify ownership in multi-user context
        $userId = $request->user()?->id;
        if ($connection->user_id && $userId && $connection->user_id !== $userId) {
            throw new \RuntimeException(
                'Connection does not belong to the current user.'
            );
        }

        // Verify tenant in multi-tenant context
        $tenantId = $request->session()->get('exact_oauth_tenant_id');
        if ($connection->tenant_id && $tenantId && $connection->tenant_id !== $tenantId) {
            throw new \RuntimeException(
                'Connection does not belong to the current tenant.'
            );
        }

        return $connection;
    }

    /**
     * Clean up OAuth session data
     */
    protected function cleanupSession(Request $request): void
    {
        $request->session()->forget([
            'exact_oauth_state',
            'exact_oauth_connection_id',
            'exact_oauth_redirect_to',
            'exact_oauth_tenant_id',
        ]);
    }

    /**
     * Sync available divisions after successful OAuth
     */
    protected function syncDivisions(ExactConnection $connection, bool $debug): void
    {
        try {
            $syncAction = Config::getAction(
                'sync_divisions',
                SyncDivisionsAction::class
            );

            $result = $syncAction->execute($connection);

            if ($debug) {
                Log::info('Synced divisions after OAuth', [
                    'connection_id' => $connection->id,
                    'created' => $result['created'],
                    'updated' => $result['updated'],
                    'total' => $result['total'],
                ]);
            }
        } catch (\Throwable $e) {
            // Log but don't fail OAuth - divisions can be synced later
            Log::warning('Failed to sync divisions after OAuth', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle successful OAuth callback
     */
    protected function handleSuccess(Request $request, ExactConnection $connection, ?string $redirectTo = null): RedirectResponse|JsonResponse
    {
        // For API requests, return JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Successfully connected to Exact Online',
                'connection_id' => $connection->id,
                'division' => $connection->division,
            ]);
        }

        // Use provided redirect URL or fall back to configured success URL
        if ($redirectTo) {
            return redirect($redirectTo)
                ->with('exact_oauth_success', 'Successfully connected to Exact Online')
                ->with('exact_connection_id', $connection->id);
        }

        // Use configured success URL
        $successUrl = config('exactonline-laravel-api.oauth.success_url', '/dashboard');

        // Replace {connection} placeholder if present
        if (str_contains($successUrl, '{connection}')) {
            $successUrl = str_replace('{connection}', (string) $connection->id, $successUrl);
        }

        return redirect()->to($successUrl)
            ->with('exact_oauth_success', 'Successfully connected to Exact Online')
            ->with('exact_connection_id', $connection->id);
    }

    /**
     * Handle failed OAuth callback
     */
    protected function handleFailure(Request $request, \Throwable $exception, ?string $redirectTo = null): RedirectResponse|JsonResponse
    {
        // Prepare error message
        $errorMessage = 'Failed to connect to Exact Online';

        // Add more detail for certain exception types
        if ($exception instanceof TokenRefreshException ||
            $exception instanceof \InvalidArgumentException ||
            $exception instanceof \RuntimeException) {
            $errorMessage .= ': '.$exception->getMessage();
        } else {
            // For other exceptions, be more generic in user-facing message
            $errorMessage .= '. Please try again.';
        }

        // For API requests, return JSON
        if ($request->expectsJson()) {
            $statusCode = match (true) {
                $exception instanceof \RuntimeException && str_contains($exception->getMessage(), 'CSRF') => 403,
                $exception instanceof \InvalidArgumentException => 400,
                default => 500,
            };

            return response()->json([
                'success' => false,
                'error' => $errorMessage,
                'message' => $exception->getMessage(),
            ], $statusCode);
        }

        // Use session redirect URL or configured failure URL
        $failureUrl = $redirectTo ?? config('exactonline-laravel-api.oauth.failure_url', '/');

        return redirect()->to($failureUrl)
            ->with('exact_oauth_error', $errorMessage);
    }
}
