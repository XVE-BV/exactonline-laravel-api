<?php

use Skylence\ExactonlineLaravelApi\Actions;

return [
    /*
    |--------------------------------------------------------------------------
    | OAuth Configuration
    |--------------------------------------------------------------------------
    |
    | These values are used to authenticate with Exact Online's OAuth 2.0
    | service. You can obtain these from your Exact Online app registration.
    |
    */
    'oauth' => [
        'client_id' => env('EXACT_CLIENT_ID'),
        'client_secret' => env('EXACT_CLIENT_SECRET'),
        'redirect_url' => env('EXACT_REDIRECT_URL', '/exact/oauth/callback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Action Classes
    |--------------------------------------------------------------------------
    |
    | These are the action classes that handle various Exact Online operations.
    | You can override any of these with your own implementations by extending
    | the default classes or implementing the same public interface.
    |
    */
    'actions' => [
        // OAuth Flow
        'acquire_access_token' => Actions\OAuth\AcquireAccessTokenAction::class,
        'refresh_access_token' => Actions\OAuth\RefreshAccessTokenAction::class,
        'store_tokens' => Actions\OAuth\StoreTokensAction::class,
        'revoke_tokens' => Actions\OAuth\RevokeTokensAction::class,
        'monitor_refresh_token_expiry' => Actions\OAuth\MonitorRefreshTokenExpiryAction::class,

        // API Operations
        'create_sales_invoice' => Actions\API\CreateSalesInvoiceAction::class,
        'update_account' => Actions\API\UpdateAccountAction::class,
        'get_sales_invoices' => Actions\API\GetSalesInvoicesAction::class,
        'get_accounts' => Actions\API\GetAccountsAction::class,
        'get_account' => Actions\API\GetAccountAction::class,
        'create_account' => Actions\API\CreateAccountAction::class,
        'download_document' => Actions\API\DownloadDocumentAction::class,
        'batch_sync_entities' => Actions\API\BatchSyncEntitiesAction::class,

        // Webhooks
        'register_webhook' => Actions\Webhooks\RegisterWebhookAction::class,
        'validate_webhook_signature' => Actions\Webhooks\ValidateWebhookSignatureAction::class,
        'process_webhook_payload' => Actions\Webhooks\ProcessWebhookPayloadAction::class,
        'dispatch_webhook_event' => Actions\Webhooks\DispatchWebhookEventAction::class,

        // Rate Limiting
        'check_rate_limit' => Actions\RateLimit\CheckRateLimitAction::class,
        'wait_for_rate_limit_reset' => Actions\RateLimit\WaitForRateLimitResetAction::class,
        'track_rate_limit_usage' => Actions\RateLimit\TrackRateLimitUsageAction::class,

        // Connection Management
        'create_connection' => Actions\Connection\CreateConnectionAction::class,
        'switch_division' => Actions\Connection\SwitchDivisionAction::class,
        'validate_connection' => Actions\Connection\ValidateConnectionAction::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | These are the Eloquent models used by the package. You can override
    | these with your own models if you need custom functionality.
    |
    */
    'models' => [
        'connection' => \Skylence\ExactonlineLaravelApi\Models\ExactConnection::class,
        'webhook' => \Skylence\ExactonlineLaravelApi\Models\ExactWebhook::class,
        'rate_limit' => \Skylence\ExactonlineLaravelApi\Models\ExactRateLimit::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure how the package handles Exact Online's rate limits.
    | Exact Online has both minutely (60 calls/minute) and daily limits.
    |
    */
    'rate_limiting' => [
        // If true, the package will automatically wait when hitting the minutely limit
        'wait_on_minutely_limit' => env('EXACT_WAIT_ON_RATE_LIMIT', true),
        
        // If true, the package will throw an exception when hitting the daily limit
        // If false, it will attempt to wait (not recommended as daily limits reset after 24 hours)
        'throw_on_daily_limit' => env('EXACT_THROW_ON_DAILY_LIMIT', true),
        
        // Maximum time to wait for rate limit reset (in seconds)
        'max_wait_seconds' => env('EXACT_MAX_WAIT_SECONDS', 65),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook handling for real-time updates from Exact Online.
    |
    */
    'webhooks' => [
        // The URL path where webhooks will be received
        'path' => env('EXACT_WEBHOOK_PATH', '/exact/webhooks'),
        
        // Queue to use for processing webhooks (null for sync processing)
        'queue' => env('EXACT_WEBHOOK_QUEUE', null),
        
        // Webhook topics to subscribe to (leave empty for all)
        'topics' => [
            'Accounts',
            'SalesInvoices',
            'Contacts',
            'Items',
            'Documents',
            'GLAccounts',
            'FinancialTransactions',
            // Add more topics as needed
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for Exact Online connections.
    |
    */
    'connection' => [
        // Base URL for Exact Online API (can be overridden per connection)
        'base_url' => env('EXACT_BASE_URL', 'https://start.exactonline.nl'),
        
        // Default division (administration) to use
        'division' => env('EXACT_DIVISION', null),
        
        // Cache settings for tokens
        'cache' => [
            // Cache store to use for distributed locking during token refresh
            'store' => env('EXACT_CACHE_STORE', 'redis'),
            
            // Lock timeout in seconds (should be less than token lifetime)
            'lock_timeout' => env('EXACT_LOCK_TIMEOUT', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Relying Party
    |--------------------------------------------------------------------------
    |
    | Information about your application for OAuth flows.
    |
    */
    'relying_party' => [
        'name' => env('EXACT_RELYING_PARTY_NAME', config('app.name')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging for the package.
    |
    */
    'logging' => [
        // Log channel to use
        'channel' => env('EXACT_LOG_CHANNEL', 'daily'),
        
        // Log API requests and responses
        'log_requests' => env('EXACT_LOG_REQUESTS', false),
        
        // Log token refresh operations
        'log_token_refresh' => env('EXACT_LOG_TOKEN_REFRESH', true),
        
        // Log rate limit hits
        'log_rate_limits' => env('EXACT_LOG_RATE_LIMITS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | Settings for testing and development.
    |
    */
    'testing' => [
        // Use Exact Online sandbox environment
        'use_sandbox' => env('EXACT_USE_SANDBOX', false),
        
        // Sandbox URL
        'sandbox_url' => env('EXACT_SANDBOX_URL', 'https://start.exactonline.nl'),
    ],
];
