<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Http\Controllers\OAuth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class RedirectToExactController extends Controller
{
    /**
     * Redirect user to Exact Online OAuth authorization page
     *
     * This controller generates the OAuth authorization URL and redirects
     * the user to Exact Online for authentication and authorization.
     *
     * @param  int|null  $connectionId  Optional existing connection to re-authorize
     *
     * @throws ConnectionException
     */
    public function __invoke(Request $request, ?int $connectionId = null): RedirectResponse|JsonResponse
    {
        try {
            $debug = config('exactonline-laravel-api.logging.debug', false);

            if ($debug) {
                Log::info('OAuth redirect initiated', [
                    'connection_id' => $connectionId,
                    'user_id' => $request->user()?->id,
                    'session_id' => $request->session()->getId(),
                    'is_ajax' => $request->ajax(),
                ]);
            }

            // Generate state for CSRF protection
            $state = $this->generateState();

            if ($debug) {
                Log::debug('OAuth state generated', [
                    'state' => substr($state, 0, 10).'...',
                ]);
            }

            // Store OAuth session data
            $this->storeOAuthSession($request, $state, $connectionId);

            // Get or create connection record
            $connection = $this->getOrCreateConnection($request, $connectionId);

            if ($debug) {
                Log::debug('OAuth connection retrieved', [
                    'connection_id' => $connection->id,
                    'has_client_id' => ! empty($connection->client_id),
                    'has_client_secret' => ! empty($connection->client_secret),
                    'redirect_url' => $connection->redirect_url,
                    'base_url' => $connection->base_url,
                ]);
            }

            // Validate connection has required OAuth credentials
            $this->validateConnectionCredentials($connection);

            // Store connection ID in session for callback
            $request->session()->put('exact_oauth_connection_id', $connection->id);

            // Generate authorization URL
            $authUrl = $this->generateAuthorizationUrl($connection, $state);

            if ($debug) {
                Log::info('Redirecting to Exact Online for OAuth authorization', [
                    'connection_id' => $connection->id,
                    'user_id' => $request->user()?->id,
                    'force_login' => config('exactonline-laravel-api.oauth.force_login', false),
                    'auth_url' => $authUrl,
                    'session_state_stored' => $request->session()->get('exact_oauth_state') === $state,
                    'session_connection_id_stored' => $request->session()->get('exact_oauth_connection_id'),
                ]);
            }

            // For AJAX/Livewire requests, return URL for client-side redirect
            // This avoids CORS issues when redirecting to external OAuth providers
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Livewire')) {
                return response()->json([
                    'redirect' => $authUrl,
                ]);
            }

            return redirect()->away($authUrl);

        } catch (\Exception $e) {
            Log::error('Failed to redirect to Exact Online', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $failureUrl = config('exactonline-laravel-api.oauth.failure_url', '/');

            // For AJAX requests, return error as JSON
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Livewire')) {
                return response()->json([
                    'error' => 'Failed to initiate Exact Online connection: '.$e->getMessage(),
                    'redirect' => $failureUrl,
                ], 500);
            }

            return redirect()->to($failureUrl)
                ->with('error', 'Failed to initiate Exact Online connection: '.$e->getMessage());
        }
    }

    /**
     * Validate that the connection has required OAuth credentials
     *
     * @throws ConnectionException
     */
    protected function validateConnectionCredentials(ExactConnection $connection): void
    {
        if (empty($connection->client_id) || empty($connection->client_secret)) {
            throw ConnectionException::invalidConfiguration(
                'OAuth client ID and secret must be configured on the connection. '.
                'Please edit the connection and set the Client ID and Client Secret.'
            );
        }

        if (empty($connection->redirect_url)) {
            throw ConnectionException::invalidConfiguration(
                'OAuth redirect URL must be configured on the connection. '.
                'Please edit the connection and set the Redirect URL.'
            );
        }
    }

    /**
     * Generate a random state value for CSRF protection
     */
    protected function generateState(): string
    {
        return Str::random(40);
    }

    /**
     * Store OAuth session data
     */
    protected function storeOAuthSession(Request $request, string $state, ?int $connectionId): void
    {
        $request->session()->put('exact_oauth_state', $state);

        if ($connectionId) {
            $request->session()->put('exact_oauth_connection_id', $connectionId);
        }

        // Store where to redirect after successful OAuth
        $redirectTo = $request->input('redirect_to') ?? $request->headers->get('referer');
        if ($redirectTo) {
            $request->session()->put('exact_oauth_redirect_to', $redirectTo);
        }

        // Store tenant ID if in multi-tenant context
        if ($request->has('tenant_id') || $request->session()->has('tenant_id')) {
            $tenantId = $request->input('tenant_id') ?? $request->session()->get('tenant_id');
            $request->session()->put('exact_oauth_tenant_id', $tenantId);
        }
    }

    /**
     * Get existing connection or create a new one
     *
     * @throws ConnectionException
     */
    protected function getOrCreateConnection(Request $request, ?int $connectionId): ExactConnection
    {
        if ($connectionId) {
            $connection = ExactConnection::find($connectionId);

            if (! $connection) {
                throw ConnectionException::notFound((string) $connectionId);
            }

            // Verify ownership in multi-user context
            if ($connection->user_id && $request->user() && $connection->user_id !== $request->user()->id) {
                throw ConnectionException::notFound((string) $connectionId);
            }

            // Connection already has its credentials configured via Filament
            return $connection;
        }

        // Create a new connection record using global config
        return $this->createNewConnection($request);
    }

    /**
     * Create a new connection record using global config
     *
     * @throws ConnectionException
     */
    protected function createNewConnection(Request $request): ExactConnection
    {
        // Validate global config is set when creating new connection
        $clientId = Config::getClientId();
        $clientSecret = Config::getClientSecret();

        if (empty($clientId) || empty($clientSecret)) {
            throw ConnectionException::invalidConfiguration(
                'OAuth client ID and secret must be configured to create a new connection. '.
                'Please set EXACT_CLIENT_ID and EXACT_CLIENT_SECRET in your .env file, '.
                'or create a connection manually in the admin panel.'
            );
        }

        $userId = $request->user()?->id;
        $tenantId = $request->session()->get('exact_oauth_tenant_id');

        // Generate a descriptive name
        $name = 'Exact Online';
        $userName = data_get($request->user(), 'name');
        if ($userId && is_string($userName) && $userName !== '') {
            $name .= ' - '.$userName;
        }
        $name .= ' ('.now()->format('Y-m-d H:i').')';

        return ExactConnection::create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_url' => $this->getRedirectUrl($request),
            'base_url' => config('exactonline-laravel-api.connection.base_url', 'https://start.exactonline.nl'),
            'is_active' => false, // Will be activated after successful token acquisition
            'name' => $name,
            'metadata' => [
                'created_from_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);
    }

    /**
     * Generate the OAuth authorization URL
     */
    protected function generateAuthorizationUrl(ExactConnection $connection, string $state): string
    {
        $baseUrl = $connection->base_url;
        $clientId = $connection->client_id;
        $redirectUrl = $connection->redirect_url;
        $forceLogin = config('exactonline-laravel-api.oauth.force_login', false);

        // Build authorization URL with required parameters
        $params = http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUrl,
            'state' => $state,
            'force_login' => $forceLogin ? '1' : '0',
        ]);

        return "{$baseUrl}/api/oauth2/auth?{$params}";
    }

    /**
     * Get the redirect URL for OAuth callback
     */
    protected function getRedirectUrl(Request $request): string
    {
        $configuredUrl = Config::getRedirectUrl();

        // If it's a relative URL, make it absolute
        if (! filter_var($configuredUrl, FILTER_VALIDATE_URL)) {
            return $request->getSchemeAndHttpHost().$configuredUrl;
        }

        return $configuredUrl;
    }
}
