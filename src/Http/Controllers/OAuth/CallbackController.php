<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Http\Controllers\OAuth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\AcquireAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Exceptions\TokenRefreshException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class CallbackController extends Controller
{
    /**
     * Handle the OAuth callback from Exact Online.
     */
    public function __invoke(
        Request $request,
        AcquireAccessTokenAction $acquireTokenAction
    ): RedirectResponse|Response {
        // Validate the OAuth callback
        try {
            $this->validateCallback($request);
        } catch (\Exception $e) {
            Log::error('OAuth callback validation failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->handleCallbackError($e->getMessage());
        }

        // Get or create connection
        $connection = $this->getOrCreateConnection($request);

        // Exchange authorization code for tokens
        try {
            $tokens = $acquireTokenAction->execute(
                $connection,
                $request->input('code')
            );

            Log::info('OAuth callback successful', [
                'connection_id' => $connection->id,
            ]);

            // Clear OAuth session data
            $this->clearOAuthSession($request);

            // Redirect to success URL
            return $this->handleCallbackSuccess($request, $connection);

        } catch (TokenRefreshException $e) {
            Log::error('Failed to acquire tokens in OAuth callback', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return $this->handleCallbackError($e->getMessage());
        }
    }

    /**
     * Validate the OAuth callback request.
     *
     * @throws \Exception
     */
    protected function validateCallback(Request $request): void
    {
        // Check for error from Exact Online
        if ($request->has('error')) {
            $error = $request->input('error');
            $errorDescription = $request->input('error_description', 'Unknown error');

            throw new \Exception("OAuth error: {$error} - {$errorDescription}");
        }

        // Validate authorization code is present
        if (! $request->has('code')) {
            throw new \Exception('Authorization code missing from callback');
        }

        // Validate state for CSRF protection
        $state = $request->input('state');
        $sessionState = $request->session()->get('exact_oauth_state');

        if (! $state || ! $sessionState || $state !== $sessionState) {
            throw new \Exception('Invalid state parameter - possible CSRF attack');
        }
    }

    /**
     * Get existing connection or create a new one.
     */
    protected function getOrCreateConnection(Request $request): ExactConnection
    {
        // Check if we're re-authenticating an existing connection
        $connectionId = $request->session()->get('exact_oauth_connection_id');

        if ($connectionId) {
            $connection = ExactConnection::find($connectionId);
            if ($connection) {
                return $connection;
            }
        }

        // Create a new connection
        return $this->createNewConnection($request);
    }

    /**
     * Create a new Exact Online connection.
     */
    protected function createNewConnection(Request $request): ExactConnection
    {
        $connection = new ExactConnection;

        // Set OAuth configuration
        $connection->client_id = Config::getClientId();
        $connection->client_secret = Config::getClientSecret();
        $connection->redirect_url = Config::getRedirectUrl();
        $connection->base_url = config('exactonline-laravel-api.connection.base_url', 'https://start.exactonline.nl');

        // Set user/tenant if available
        if ($request->user()) {
            $connection->user_id = $request->user()->id;
        }

        // Set tenant if multi-tenant
        if ($request->has('tenant_id')) {
            $connection->tenant_id = $request->input('tenant_id');
        }

        // Set a default name
        $connection->name = 'Exact Online Connection '.now()->format('Y-m-d H:i');

        $connection->save();

        return $connection;
    }

    /**
     * Clear OAuth session data.
     */
    protected function clearOAuthSession(Request $request): void
    {
        $request->session()->forget([
            'exact_oauth_state',
            'exact_oauth_connection_id',
            'exact_oauth_redirect_to',
        ]);
    }

    /**
     * Handle successful OAuth callback.
     */
    protected function handleCallbackSuccess(Request $request, ExactConnection $connection): RedirectResponse
    {
        // Check for custom redirect URL
        $redirectTo = $request->session()->get('exact_oauth_redirect_to');

        if ($redirectTo) {
            return redirect($redirectTo)->with('success', 'Successfully connected to Exact Online');
        }

        // Use configured success URL
        $successUrl = config('exactonline-laravel-api.oauth.success_url', '/');

        return redirect($successUrl)
            ->with('success', 'Successfully connected to Exact Online')
            ->with('exact_connection_id', $connection->id);
    }

    /**
     * Handle OAuth callback error.
     */
    protected function handleCallbackError(string $error): RedirectResponse|Response
    {
        // Check if we should return JSON for API requests
        if (request()->expectsJson()) {
            return response()->json([
                'error' => 'OAuth authentication failed',
                'message' => $error,
            ], 400);
        }

        // Use configured error URL
        $errorUrl = config('exactonline-laravel-api.oauth.error_url', '/');

        return redirect($errorUrl)
            ->with('error', 'Failed to connect to Exact Online: '.$error);
    }
}
