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
     * Redirect the user to Exact Online for OAuth authorization.
     *
     * @param Request $request
     * @param int|null $connectionId Optional existing connection to re-authenticate
     * @return RedirectResponse
     */
    public function __invoke(Request $request, ?int $connectionId = null): RedirectResponse
    {
        // Generate a random state for CSRF protection
        $state = Str::random(40);
        
        // Store state in session for validation on callback
        $request->session()->put('exact_oauth_state', $state);
        
        // Store connection ID if re-authenticating
        if ($connectionId !== null) {
            $request->session()->put('exact_oauth_connection_id', $connectionId);
        }
        
        // Store intended URL to redirect back to after OAuth
        if ($request->has('redirect_to')) {
            $request->session()->put('exact_oauth_redirect_to', $request->input('redirect_to'));
        }

        // Build the authorization URL
        $authorizationUrl = $this->buildAuthorizationUrl($state);

        Log::info('Redirecting to Exact Online for OAuth authorization', [
            'connection_id' => $connectionId,
            'state' => substr($state, 0, 10) . '...',
        ]);

        return redirect()->away($authorizationUrl);
    }

    /**
     * Build the Exact Online authorization URL.
     *
     * @param string $state
     * @return string
     */
    protected function buildAuthorizationUrl(string $state): string
    {
        $baseUrl = config('exactonline-laravel-api.connection.base_url', 'https://start.exactonline.nl');
        $clientId = Config::getClientId();
        $redirectUrl = $this->getRedirectUrl();

        // Build query parameters
        $queryParams = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUrl,
            'response_type' => 'code',
            'state' => $state,
            'force_login' => config('exactonline-laravel-api.oauth.force_login', '0'),
        ]);

        // Exact Online OAuth authorization endpoint
        return "{$baseUrl}/api/oauth2/auth?{$queryParams}";
    }

    /**
     * Get the OAuth callback redirect URL.
     *
     * @return string
     */
    protected function getRedirectUrl(): string
    {
        $configUrl = Config::getRedirectUrl();
        
        // If it's a relative URL, make it absolute
        if (! filter_var($configUrl, FILTER_VALIDATE_URL)) {
            return url($configUrl);
        }
        
        return $configUrl;
    }
}
