<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Http\Controllers\OAuth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
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
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        try {
            // Validate state parameter for CSRF protection
            $this->validateState($request);

            // Get authorization code
            $authorizationCode = $request->input('code');
            
            if (! $authorizationCode) {
                throw new \InvalidArgumentException('No authorization code received from Exact Online');
            }

            // Check if error was returned
            if ($request->has('error')) {
                $error = $request->input('error');
                $errorDescription = $request->input('error_description', 'Unknown error');
                throw new \RuntimeException("OAuth error from Exact Online: {$error} - {$errorDescription}");
            }

            // Get the connection
            $connection = $this->getConnection($request);

            // Exchange authorization code for tokens
            $action = Config::getAction(
                'acquire_access_token',
                AcquireAccessTokenAction::class
            );

            $tokens = $action->execute($connection, $authorizationCode);

            Log::info('OAuth callback successful', [
                'connection_id' => $connection->id,
                'token_expires_at' => $tokens['expires_at'],
            ]);

            // Clean up session
            $this->cleanupSession($request);

            // Redirect to success URL
            return $this->redirectToSuccess($request, $connection);

        } catch (\Throwable $e) {
            Log::error('OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Clean up session
            $this->cleanupSession($request);

            // Redirect to failure URL
            return $this->redirectToFailure($request, $e);
        }
    }

    /**
     * Validate the state parameter for CSRF protection
     *
     * @param Request $request
     * @return void
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
     * Get the connection for this OAuth flow
     *
     * @param Request $request
     * @return ExactConnection
     * @throws \RuntimeException
     */
    protected function getConnection(Request $request): ExactConnection
    {
        // Check if we have a connection ID in session (re-authorization)
        $connectionId = $request->session()->get('exact_oauth_connection_id');
        
        if ($connectionId) {
            return ExactConnection::findOrFail($connectionId);
        }

        // Try to find the most recent inactive connection for this user
        $userId = $request->user()?->id;
        
        if ($userId) {
            $connection = ExactConnection::where('user_id', $userId)
                ->where('is_active', false)
                ->latest()
                ->first();
                
            if ($connection) {
                return $connection;
            }
        }

        // Try to find any recent inactive connection
        $connection = ExactConnection::where('is_active', false)
            ->latest()
            ->first();
            
        if (! $connection) {
            throw new \RuntimeException('No connection found for OAuth callback');
        }

        return $connection;
    }

    /**
     * Clean up OAuth session data
     *
     * @param Request $request
     * @return void
     */
    protected function cleanupSession(Request $request): void
    {
        $request->session()->forget([
            'exact_oauth_state',
            'exact_oauth_connection_id',
        ]);
    }

    /**
     * Redirect to success URL after successful OAuth
     *
     * @param Request $request
     * @param ExactConnection $connection
     * @return RedirectResponse
     */
    protected function redirectToSuccess(Request $request, ExactConnection $connection): RedirectResponse
    {
        $successUrl = config('exactonline-laravel-api.oauth.success_url', '/');

        // Add connection ID to URL if it contains a placeholder
        if (str_contains($successUrl, '{connection}')) {
            $successUrl = str_replace('{connection}', (string) $connection->id, $successUrl);
        }

        // Flash success message
        $request->session()->flash('exact_oauth_success', 'Successfully connected to Exact Online');
        $request->session()->flash('exact_connection_id', $connection->id);

        return redirect()->to($successUrl);
    }

    /**
     * Redirect to failure URL after failed OAuth
     *
     * @param Request $request
     * @param \Throwable $exception
     * @return RedirectResponse
     */
    protected function redirectToFailure(Request $request, \Throwable $exception): RedirectResponse
    {
        $failureUrl = config('exactonline-laravel-api.oauth.failure_url', '/');

        // Flash error message
        $errorMessage = 'Failed to connect to Exact Online';
        
        if ($exception instanceof TokenRefreshException || $exception instanceof \InvalidArgumentException) {
            $errorMessage .= ': ' . $exception->getMessage();
        }

        $request->session()->flash('exact_oauth_error', $errorMessage);

        return redirect()->to($failureUrl);
    }
}
