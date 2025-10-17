<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Http\Controllers\OAuth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
     * @param Request $request
     * @param int|null $connectionId Optional existing connection to re-authorize
     * @return RedirectResponse
     */
    public function __invoke(Request $request, ?int $connectionId = null): RedirectResponse
    {
        // Generate state for CSRF protection
        $state = $this->generateState();
        
        // Store state in session for verification on callback
        $request->session()->put('exact_oauth_state', $state);
        
        // Store connection ID if re-authorizing existing connection
        if ($connectionId) {
            $request->session()->put('exact_oauth_connection_id', $connectionId);
        }

        // Get or create connection record
        $connection = $this->getOrCreateConnection($request, $connectionId);
        
        // Generate authorization URL
        $authUrl = $this->generateAuthorizationUrl($connection, $state);

        Log::info('Redirecting to Exact Online for OAuth authorization', [
            'connection_id' => $connection->id,
            'auth_url' => $authUrl,
        ]);

        return redirect()->away($authUrl);
    }

    /**
     * Generate a random state value for CSRF protection
     *
     * @return string
     */
    protected function generateState(): string
    {
        return Str::random(40);
    }

    /**
     * Get existing connection or create a new one
     *
     * @param Request $request
     * @param int|null $connectionId
     * @return ExactConnection
     */
    protected function getOrCreateConnection(Request $request, ?int $connectionId): ExactConnection
    {
        if ($connectionId) {
            $connection = ExactConnection::findOrFail($connectionId);
        } else {
            // Create a new connection record
            $connection = ExactConnection::create([
                'user_id' => $request->user()?->id,
                'tenant_id' => $request->session()->get('tenant_id'),
                'client_id' => Config::getClientId(),
                'client_secret' => Config::getClientSecret(),
                'redirect_url' => $this->getRedirectUrl($request),
                'base_url' => config('exactonline-laravel-api.connection.base_url', 'https://start.exactonline.nl'),
                'is_active' => false, // Will be activated after successful token acquisition
                'name' => 'Exact Online Connection',
            ]);
        }

        return $connection;
    }

    /**
     * Generate the OAuth authorization URL
     *
     * @param ExactConnection $connection
     * @param string $state
     * @return string
     */
    protected function generateAuthorizationUrl(ExactConnection $connection, string $state): string
    {
        $baseUrl = $connection->base_url;
        $clientId = $connection->client_id;
        $redirectUrl = $connection->redirect_url;

        // Build authorization URL with required parameters
        $params = http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUrl,
            'state' => $state,
            'force_login' => 0, // Set to 1 to force re-authentication
        ]);

        return "{$baseUrl}/api/oauth2/auth?{$params}";
    }

    /**
     * Get the redirect URL for OAuth callback
     *
     * @param Request $request
     * @return string
     */
    protected function getRedirectUrl(Request $request): string
    {
        $configuredUrl = Config::getRedirectUrl();
        
        // If it's a relative URL, make it absolute
        if (! filter_var($configuredUrl, FILTER_VALIDATE_URL)) {
            return $request->getSchemeAndHttpHost() . $configuredUrl;
        }

        return $configuredUrl;
    }
}
