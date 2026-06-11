<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Mcp\Server\McpServiceProvider;
use XVE\ExactonlineLaravelApi\Mcp\ExactMcpServer;
use XVE\ExactonlineLaravelApi\Mcp\Tools\ExactApiReadTool;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class FakeAnonAccountsAction
{
    public function execute($connection, array $options = []): Collection
    {
        return collect([
            [
                'ID' => '11111111-0000-0000-0000-000000000001',
                'Code' => 'ACC-001',
                'Name' => 'Acme Corp',
                'Email' => 'info@acme.com',
                'Phone' => '+32 2 555 1234',
                'AddressLine1' => 'Rue de la Paix 42',
                'City' => 'Brussels',
                'Postcode' => '1000',
                'Country' => 'BE',
                'VATNumber' => 'BE0123456789',
            ],
        ]);
    }
}

class FakeAnonOrdersAction
{
    public function execute($connection, array $options = []): Collection
    {
        return collect([
            [
                'OrderNumber' => 12345,
                'OrderedByName' => 'Acme Corp',
                'AmountDC' => 500.00,
                'AmountFC' => 500.00,
                'VATAmountDC' => 105.00,
                'YourRef' => 'PO-2026-001',
                'OrderDate' => '2026-01-15T00:00:00',
                'Status' => 20,
                'Currency' => 'EUR',
            ],
        ]);
    }
}

class FakeAnonSingleAccountAction
{
    public function execute($connection, string $id, array $options = []): ?array
    {
        return [
            'ID' => $id,
            'Code' => 'ACC-001',
            'Name' => 'Acme Corp',
            'Email' => 'info@acme.com',
        ];
    }
}

beforeEach(function () {
    $this->app->register(McpServiceProvider::class);

    config()->set('exactonline-laravel-api.mcp.limits.default_limit', 50);
    config()->set('exactonline-laravel-api.mcp.limits.max_limit', 200);

    ExactConnection::factory()->create([
        'name' => 'MCP anon test connection',
        'is_active' => true,
        'last_used_at' => now(),
        'access_token' => 'access',
        'refresh_token' => 'refresh',
        'token_expires_at' => now()->addHour()->timestamp,
    ]);
});

it('anonymizes PII fields in account data by default (EXACT_MCP_ANONYMIZE defaults true)', function () {
    config()->set('exactonline-laravel-api.actions.get_accounts', FakeAnonAccountsAction::class);

    ExactMcpServer::tool(ExactApiReadTool::class, ['entity' => 'get_accounts'])
        ->assertOk()
        ->assertDontSee('Acme Corp')
        ->assertDontSee('info@acme.com')
        ->assertDontSee('Rue de la Paix 42')
        ->assertDontSee('Brussels')
        ->assertDontSee('BE0123456789')
        ->assertSee('Anoniem-')
        ->assertSee('@example.com')
        ->assertSee('Anonstraat')
        ->assertSee('Anonstad')
        ->assertSee('ANON')
        ->assertSee('ACC-001');
});

it('returns real data when EXACT_MCP_ANONYMIZE=false', function () {
    config()->set('exactonline-laravel-api.actions.get_accounts', FakeAnonAccountsAction::class);
    config()->set('exactonline-laravel-api.mcp.anonymize', false);

    ExactMcpServer::tool(ExactApiReadTool::class, ['entity' => 'get_accounts'])
        ->assertOk()
        ->assertSee('Acme Corp')
        ->assertSee('info@acme.com')
        ->assertSee('Rue de la Paix 42')
        ->assertSee('Brussels');
});

it('order/amount/number fields are identical whether anonymization is on or off', function () {
    config()->set('exactonline-laravel-api.actions.get_sales_orders', FakeAnonOrdersAction::class);

    // With anonymization on: business data present, customer name absent
    config()->set('exactonline-laravel-api.mcp.anonymize', true);
    $response = ExactMcpServer::tool(ExactApiReadTool::class, ['entity' => 'get_sales_orders'])->assertOk();
    foreach (['12345', '500', '105', 'PO-2026-001', '2026-01-15', 'EUR'] as $value) {
        $response->assertSee($value);
    }
    $response->assertDontSee('Acme Corp');

    // With anonymization off: all data present including customer name
    config()->set('exactonline-laravel-api.mcp.anonymize', false);
    $response = ExactMcpServer::tool(ExactApiReadTool::class, ['entity' => 'get_sales_orders'])->assertOk();
    foreach (['12345', '500', '105', 'PO-2026-001', '2026-01-15', 'EUR'] as $value) {
        $response->assertSee($value);
    }
    $response->assertSee('Acme Corp');
});

it('anonymizes PII on the single-record path too', function () {
    config()->set('exactonline-laravel-api.actions.get_account', FakeAnonSingleAccountAction::class);

    ExactMcpServer::tool(ExactApiReadTool::class, [
        'entity' => 'get_account',
        'id' => '11111111-0000-0000-0000-000000000001',
    ])
        ->assertOk()
        ->assertDontSee('Acme Corp')
        ->assertSee('Anoniem-')
        ->assertSee('ACC-001');
});
