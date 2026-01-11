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
        'success_url' => env('EXACT_OAUTH_SUCCESS_URL', '/dashboard'),
        'failure_url' => env('EXACT_OAUTH_FAILURE_URL', '/'),
        'force_login' => env('EXACT_FORCE_LOGIN', false),
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

        // API Operations - Accounts
        'get_accounts' => Actions\API\GetAccountsAction::class,
        'get_account' => Actions\API\GetAccountAction::class,
        'create_account' => Actions\API\CreateAccountAction::class,
        'update_account' => Actions\API\UpdateAccountAction::class,
        'sync_account' => Actions\API\SyncAccountAction::class,

        // API Operations - Contacts
        'get_contacts' => Actions\API\GetContactsAction::class,
        'get_contact' => Actions\API\GetContactAction::class,
        'create_contact' => Actions\API\CreateContactAction::class,
        'update_contact' => Actions\API\UpdateContactAction::class,
        'sync_contact' => Actions\API\SyncContactAction::class,

        // API Operations - Items
        'get_items' => Actions\API\GetItemsAction::class,
        'get_item' => Actions\API\GetItemAction::class,
        'create_item' => Actions\API\CreateItemAction::class,
        'update_item' => Actions\API\UpdateItemAction::class,
        'sync_item' => Actions\API\SyncItemAction::class,

        // API Operations - Sales Orders
        'get_sales_orders' => Actions\API\GetSalesOrdersAction::class,
        'get_sales_order' => Actions\API\GetSalesOrderAction::class,
        'create_sales_order' => Actions\API\CreateSalesOrderAction::class,
        'update_sales_order' => Actions\API\UpdateSalesOrderAction::class,
        'sync_sales_order' => Actions\API\SyncSalesOrderAction::class,

        // API Operations - Sales Invoices
        'get_sales_invoices' => Actions\API\GetSalesInvoicesAction::class,
        'get_sales_invoice' => Actions\API\GetSalesInvoiceAction::class,
        'create_sales_invoice' => Actions\API\CreateSalesInvoiceAction::class,
        'sync_sales_invoice' => Actions\API\SyncSalesInvoiceAction::class,

        // API Operations - Purchase Orders
        'get_purchase_orders' => Actions\API\GetPurchaseOrdersAction::class,
        'get_purchase_order' => Actions\API\GetPurchaseOrderAction::class,
        'create_purchase_order' => Actions\API\CreatePurchaseOrderAction::class,
        'update_purchase_order' => Actions\API\UpdatePurchaseOrderAction::class,
        'sync_purchase_order' => Actions\API\SyncPurchaseOrderAction::class,

        // API Operations - Purchase Invoices
        'get_purchase_invoices' => Actions\API\GetPurchaseInvoicesAction::class,
        'get_purchase_invoice' => Actions\API\GetPurchaseInvoiceAction::class,
        'create_purchase_invoice' => Actions\API\CreatePurchaseInvoiceAction::class,
        'sync_purchase_invoice' => Actions\API\SyncPurchaseInvoiceAction::class,

        // API Operations - Quotations
        'get_quotations' => Actions\API\GetQuotationsAction::class,
        'get_quotation' => Actions\API\GetQuotationAction::class,
        'create_quotation' => Actions\API\CreateQuotationAction::class,
        'update_quotation' => Actions\API\UpdateQuotationAction::class,
        'sync_quotation' => Actions\API\SyncQuotationAction::class,

        // API Operations - Projects
        'get_projects' => Actions\API\GetProjectsAction::class,
        'get_project' => Actions\API\GetProjectAction::class,
        'create_project' => Actions\API\CreateProjectAction::class,
        'update_project' => Actions\API\UpdateProjectAction::class,
        'sync_project' => Actions\API\SyncProjectAction::class,

        // API Operations - GL Accounts
        'get_gl_accounts' => Actions\API\GetGLAccountsAction::class,
        'get_gl_account' => Actions\API\GetGLAccountAction::class,
        'create_gl_account' => Actions\API\CreateGLAccountAction::class,
        'update_gl_account' => Actions\API\UpdateGLAccountAction::class,
        'sync_gl_account' => Actions\API\SyncGLAccountAction::class,

        // API Operations - Addresses
        'get_addresses' => Actions\API\GetAddressesAction::class,
        'get_address' => Actions\API\GetAddressAction::class,
        'create_address' => Actions\API\CreateAddressAction::class,
        'update_address' => Actions\API\UpdateAddressAction::class,
        'sync_address' => Actions\API\SyncAddressAction::class,

        // API Operations - Bank Accounts
        'get_bank_accounts' => Actions\API\GetBankAccountsAction::class,
        'get_bank_account' => Actions\API\GetBankAccountAction::class,
        'create_bank_account' => Actions\API\CreateBankAccountAction::class,
        'update_bank_account' => Actions\API\UpdateBankAccountAction::class,
        'sync_bank_account' => Actions\API\SyncBankAccountAction::class,

        // API Operations - Warehouses
        'get_warehouses' => Actions\API\GetWarehousesAction::class,
        'get_warehouse' => Actions\API\GetWarehouseAction::class,
        'create_warehouse' => Actions\API\CreateWarehouseAction::class,
        'update_warehouse' => Actions\API\UpdateWarehouseAction::class,
        'sync_warehouse' => Actions\API\SyncWarehouseAction::class,

        // API Operations - Goods Deliveries
        'get_goods_deliveries' => Actions\API\GetGoodsDeliveriesAction::class,
        'get_goods_delivery' => Actions\API\GetGoodsDeliveryAction::class,
        'create_goods_delivery' => Actions\API\CreateGoodsDeliveryAction::class,
        'sync_goods_delivery' => Actions\API\SyncGoodsDeliveryAction::class,

        // API Operations - Goods Receipts
        'get_goods_receipts' => Actions\API\GetGoodsReceiptsAction::class,
        'get_goods_receipt' => Actions\API\GetGoodsReceiptAction::class,
        'create_goods_receipt' => Actions\API\CreateGoodsReceiptAction::class,
        'sync_goods_receipt' => Actions\API\SyncGoodsReceiptAction::class,

        // API Operations - Documents
        'get_documents' => Actions\API\GetDocumentsAction::class,
        'get_document' => Actions\API\GetDocumentAction::class,
        'create_document' => Actions\API\CreateDocumentAction::class,
        'sync_document' => Actions\API\SyncDocumentAction::class,

        // API Operations - Employees (read-only)
        'get_employees' => Actions\API\GetEmployeesAction::class,
        'get_employee' => Actions\API\GetEmployeeAction::class,

        // API Operations - Journals
        'get_journals' => Actions\API\GetJournalsAction::class,
        'get_journal' => Actions\API\GetJournalAction::class,
        'create_journal' => Actions\API\CreateJournalAction::class,
        'update_journal' => Actions\API\UpdateJournalAction::class,
        'sync_journal' => Actions\API\SyncJournalAction::class,

        // API Operations - Item Groups (read-only)
        'get_item_groups' => Actions\API\GetItemGroupsAction::class,
        'get_item_group' => Actions\API\GetItemGroupAction::class,

        // API Operations - Units (read-only)
        'get_units' => Actions\API\GetUnitsAction::class,
        'get_unit' => Actions\API\GetUnitAction::class,

        // API Operations - VAT Codes
        'get_vat_codes' => Actions\API\GetVATCodesAction::class,
        'get_vat_code' => Actions\API\GetVATCodeAction::class,
        'create_vat_code' => Actions\API\CreateVATCodeAction::class,
        'update_vat_code' => Actions\API\UpdateVATCodeAction::class,
        'sync_vat_code' => Actions\API\SyncVATCodeAction::class,

        // API Operations - Payments (no create, no delete)
        'get_payments' => Actions\API\GetPaymentsAction::class,
        'get_payment' => Actions\API\GetPaymentAction::class,
        'update_payment' => Actions\API\UpdatePaymentAction::class,

        // API Operations - Webhook Subscriptions
        'get_webhook_subscriptions' => Actions\API\GetWebhookSubscriptionsAction::class,
        'get_webhook_subscription' => Actions\API\GetWebhookSubscriptionAction::class,
        'create_webhook_subscription' => Actions\API\CreateWebhookSubscriptionAction::class,
        'delete_webhook_subscription' => Actions\API\DeleteWebhookSubscriptionAction::class,

        // API Operations - Other
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

        // API Operations - Divisions
        'get_divisions' => Actions\API\GetDivisionsAction::class,
        'sync_divisions' => Actions\API\SyncDivisionsAction::class,
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
        'mapping' => \Skylence\ExactonlineLaravelApi\Models\ExactMapping::class,
        'webhook' => \Skylence\ExactonlineLaravelApi\Models\ExactWebhook::class,
        'rate_limit' => \Skylence\ExactonlineLaravelApi\Models\ExactRateLimit::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapping Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how the package handles polymorphic mappings between local
    | models and Exact Online entities. Environment isolation ensures that
    | mappings created in development don't conflict with production.
    |
    */
    'mapping' => [
        // Environment identifier for mappings (local, staging, production)
        // Used to isolate mappings between environments
        'environment' => env('APP_ENV', 'production'),
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

        // Enable debug logging (verbose OAuth flow, API calls, etc.)
        'debug' => env('EXACT_LOG_DEBUG', false),

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

    /*
    |--------------------------------------------------------------------------
    | Payload Validation
    |--------------------------------------------------------------------------
    |
    | Configure pre-flight validation of payloads before sending to Exact
    | Online API. Validation is based on JSON schemas that define field
    | types, required fields, and constraints.
    |
    */
    'validation' => [
        // Enable/disable payload validation globally
        'enabled' => env('EXACT_VALIDATION_ENABLED', true),

        // Strict mode: fail on unknown fields not defined in schema
        'strict' => env('EXACT_VALIDATION_STRICT', false),

        // Custom schema path (override package schemas)
        // Set to a path containing your own JSON schema files
        'schema_path' => null,
    ],
];
