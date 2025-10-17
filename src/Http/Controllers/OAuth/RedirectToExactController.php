<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Http\Controllers\OAuth;

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
    public function __invoke(Request $request, ?int $connectionId = null): RedirectResponse
    {
        try {
            // Validate OAuth configuration
            $this->validateOAuthConfiguration();

            // Generate state for CSRF protection
            $state = $this->generateState();

            // Store OAuth session data
            $this->storeOAuthSession($request, $state, $connectionId);

            // Get or create connection record
            $connection = $this->getOrCreateConnection($request, $connectionId);

            // Store connection ID in session for callback
            $request->session()->put('exact_oauth_connection_id', $connection->id);

            // Generate authorization URL
            $authUrl = $this->generateAuthorizationUrl($connection, $state);

            Log::info('Redirecting to Exact Online for OAuth authorization', [
                'connection_id' => $connection->id,
                'user_id' => $request->user()?->id,
                'force_login' => config('exactonline-laravel-api.oauth.force_login', false),
            ]);

            return redirect()->away($authUrl);

        } catch (\Exception $e) {
            Log::error('Failed to redirect to Exact Online', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Redirect to failure URL with error message
            $failureUrl = config('exactonline-laravel-api.oauth.failure_url', '/');

            return redirect($failureUrl)
                ->with('error', 'Failed to initiate Exact Online connection: '.$e->getMessage());
        }
    }

    /**
     * Validate that OAuth configuration is properly set
     *
     * @throws ConnectionException
     */
    protected function validateOAuthConfiguration(): void
    {
        $clientId = Config::getClientId();
        $clientSecret = Config::getClientSecret();

        if (empty($clientId) || empty($clientSecret)) {
            throw ConnectionException::invalidConfiguration(
                'OAuth client ID and secret must be configured. '.
                'Please set EXACT_CLIENT_ID and EXACT_CLIENT_SECRET in your .env file.'
            );
        }

        $redirectUrl = Config::getRedirectUrl();
        if (empty($redirectUrl)) {
            throw ConnectionException::invalidConfiguration(
                'OAuth redirect URL must be configured. '.
                'Please set EXACT_REDIRECT_URL in your .env file or config.'
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

            // Update OAuth credentials in case they changed
            $connection->update([
                'client_id' => Config::getClientId(),
                'client_secret' => Config::getClientSecret(),
                'redirect_url' => $this->getRedirectUrl($request),
            ]);

            return $connection;
        }

        // Create a new connection record
        return $this->createNewConnection($request);
    }

    /**
     * Create a new connection record
     */
    protected function createNewConnection(Request $request): ExactConnection
    {
        $userId = $request->user()?->id;
        $tenantId = $request->session()->get('exact_oauth_tenant_id');

        // Generate a descriptive name
        $name = 'Exact Online';
        if ($userId && $request->user()->name) {
            $name .= ' - '.$request->user()->name;
        }
        $name .= ' ('.now()->format('Y-m-d H:i').')';

        return ExactConnection::create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'client_id' => Config::getClientId(),
            'client_secret' => Config::getClientSecret(),
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
