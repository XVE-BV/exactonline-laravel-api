# Exact Online Laravel API

A Laravel package for integrating with the Exact Online API. Provides OAuth authentication, entity synchronization, rate limiting, and polymorphic mappings between your Laravel models and Exact Online entities.

## Features

- OAuth 2.0 authentication with automatic token refresh
- Sync entities: Accounts, Contacts, Items, Sales Orders, Invoices, and more
- Polymorphic mappings between local models and Exact Online
- Division management and switching
- Rate limit handling with automatic retry
- Webhook support for real-time updates
- Payload validation before API calls
- Custom exception hierarchy for error handling

## Installation

```bash
composer require skylence/exactonline-laravel-api
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="exactonline-laravel-api-migrations"
php artisan migrate
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag="exactonline-laravel-api-config"
```

## Configuration

Add to your `.env`:

```env
EXACT_CLIENT_ID=your-client-id
EXACT_CLIENT_SECRET=your-client-secret
EXACT_REDIRECT_URL=https://your-app.com/exact/oauth/callback
```

## OAuth Setup

### 1. Create a Connection

```php
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

$connection = ExactConnection::create([
    'name' => 'My Connection',
    'client_id' => config('exactonline-laravel-api.oauth.client_id'),
    'client_secret' => 'your-secret', // Auto-encrypted
    'redirect_url' => config('exactonline-laravel-api.oauth.redirect_url'),
    'base_url' => 'https://start.exactonline.nl',
    'is_active' => true,
]);
```

### 2. Initiate OAuth Flow

The package provides routes for OAuth:

- `GET /exact/oauth/authorize/{connection}` - Redirects to Exact Online
- `GET /exact/oauth/callback` - Handles the callback

After successful authentication, divisions are automatically synced.

### 3. Select a Division

```php
$connection->update(['division' => 123456]);
```

## Usage

### Syncing Entities

```php
use Skylence\ExactonlineLaravelApi\Support\Config;
use Skylence\ExactonlineLaravelApi\Actions\API\SyncAccountAction;

$action = Config::getAction('sync_account', SyncAccountAction::class);
$result = $action->execute($connection, $localCompany);
```

### Making Your Models Mappable

Add the `ExactMappable` trait to models you want to sync:

```php
use Skylence\ExactonlineLaravelApi\Concerns\ExactMappable;
use Skylence\ExactonlineLaravelApi\Contracts\HasExactMapping;

class Company extends Model implements HasExactMapping
{
    use ExactMappable;
}
```

This provides:

```php
// Check if mapped
$company->hasExactId($connection);

// Get Exact ID
$exactId = $company->getExactId($connection);

// Set Exact ID after sync
$company->setExactId($connection, $exactGuid, 'primary', $exactCode);

// Find by Exact ID
$company = Company::findByExactId($exactGuid, $connection);

// Mappings are auto-deleted when model is deleted
```

### Available Actions

| Entity | Actions |
|--------|---------|
| Account | get, get_all, create, update, sync |
| Contact | get, get_all, create, update, sync |
| Item | get, get_all, create, update, sync |
| Sales Order | get, get_all, create, update, sync |
| Sales Invoice | get, get_all, create, sync |
| Purchase Order | get, get_all, create, update, sync |
| Purchase Invoice | get, get_all, create, sync |
| Quotation | get, get_all, create, update, sync |
| Project | get, get_all, create, update, sync |
| GL Account | get, get_all, create, update, sync |
| Division | get_all, sync |
| Document | get, get_all, create, sync, download |
| Address | get, get_all, create, update, sync |
| Bank Account | get, get_all, create, update, sync |
| Warehouse | get, get_all, create, update, sync |
| Journal | get, get_all, create, update, sync |
| VAT Code | get, get_all, create, update, sync |

### Fetching Data

```php
use Skylence\ExactonlineLaravelApi\Actions\API\GetAccountsAction;

$action = Config::getAction('get_accounts', GetAccountsAction::class);
$accounts = $action->execute($connection, [
    'filter' => "Name eq 'Acme Corp'",
    'select' => 'ID,Name,Email',
]);
```

### Creating Entities

```php
use Skylence\ExactonlineLaravelApi\Actions\API\CreateAccountAction;

$action = Config::getAction('create_account', CreateAccountAction::class);
$result = $action->execute($connection, [
    'Name' => 'New Customer',
    'Email' => 'contact@example.com',
    'Country' => 'NL',
]);
```

## Events

Events are dispatched after sync operations:

- `AccountSynced`
- `ContactSynced`
- `ItemSynced`
- `SalesOrderSynced`
- `DivisionsSynced`
- ... and more

```php
use Skylence\ExactonlineLaravelApi\Events\AccountSynced;

Event::listen(AccountSynced::class, function ($event) {
    // $event->connection
    // $event->model
    // $event->exactId
    // $event->wasCreated
});
```

## Exception Handling

The package provides a custom exception hierarchy:

```php
use Skylence\ExactonlineLaravelApi\Exceptions\ExactOnlineException;
use Skylence\ExactonlineLaravelApi\Exceptions\AuthenticationException;
use Skylence\ExactonlineLaravelApi\Exceptions\ApiException;
use Skylence\ExactonlineLaravelApi\Exceptions\SyncException;
use Skylence\ExactonlineLaravelApi\Exceptions\EntityNotFoundException;

try {
    $action->execute($connection, $data);
} catch (AuthenticationException $e) {
    // OAuth errors (invalid credentials, expired tokens)
} catch (EntityNotFoundException $e) {
    // Entity not found in Exact Online
} catch (SyncException $e) {
    // Sync operation failed
} catch (ApiException $e) {
    // General API errors
    $statusCode = $e->getStatusCode();
    $response = $e->getResponse();
}
```

## Rate Limiting

The package automatically handles Exact Online rate limits:

```php
// config/exactonline-laravel-api.php
'rate_limiting' => [
    'wait_on_minutely_limit' => true,  // Auto-wait on 60/min limit
    'throw_on_daily_limit' => true,    // Throw on daily limit
    'max_wait_seconds' => 65,
],
```

## Payload Validation

Payloads are validated before sending to the API:

```php
// config/exactonline-laravel-api.php
'validation' => [
    'enabled' => true,
    'strict' => false,  // Fail on unknown fields
],
```

## Testing

```bash
composer test
```

## Credits

- [Jonas Vanderhaegen](https://github.com/jonasvanderhaegen)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
