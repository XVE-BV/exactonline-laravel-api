<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Mcp\Server\McpServiceProvider;
use XVE\ExactonlineLaravelApi\Mcp\ExactMcpServer;
use XVE\ExactonlineLaravelApi\Mcp\Tools\ExactApiReadTool;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class FakeMcpAccountsAction
{
    public array $received = [];

    public function execute($connection, array $options = []): Collection
    {
        $this->received = $options;

        return collect([['ID' => 'a1', 'Name' => 'X']]);
    }
}

class FakeMcpAccountAction
{
    public function execute($connection, string $id, array $options = []): ?array
    {
        return ['ID' => $id, 'Name' => 'Single'];
    }
}

class FakeMcpEmptyAccountAction
{
    public function execute($connection, string $id): ?array
    {
        return [];
    }
}

class FakeMcpGLAccountAction
{
    public function execute($connection, string $id): ?array
    {
        return ['ID' => $id, 'Code' => '4000', 'Description' => 'Plain'];
    }
}

class FakeMcpGLAccountsAction
{
    public array $received = [];

    public function execute($connection, array $options = []): Collection
    {
        $this->received = $options;

        return collect([[
            'ID' => '81ae9335-3301-4bb4-962b-759197279d5b',
            'Code' => '4000',
        ]]);
    }
}

class FakeMcpThrowableAccountsAction
{
    public function execute($connection, array $options = []): Collection
    {
        throw new Error('boom /private/path/trace.php:123');
    }
}

beforeEach(function () {
    $this->app->register(McpServiceProvider::class);

    config()->set('exactonline-laravel-api.mcp.limits.default_limit', 50);
    config()->set('exactonline-laravel-api.mcp.limits.max_limit', 200);
    ExactConnection::factory()->create([
        'name' => 'MCP test connection',
        'is_active' => true,
        'last_used_at' => now(),
        'access_token' => 'access',
        'refresh_token' => 'refresh',
        'token_expires_at' => now()->addHour()->timestamp,
    ]);
});

it('dispatches collection getters with a default limit and clamps explicit top', function () {
    $spy = new FakeMcpAccountsAction;

    $this->app->instance(FakeMcpAccountsAction::class, $spy);
    config()->set('exactonline-laravel-api.actions.get_accounts', FakeMcpAccountsAction::class);

    ExactMcpServer::tool(ExactApiReadTool::class, [
        'entity' => 'get_accounts',
    ])->assertOk()->assertSee('a1');

    expect($spy->received['top'])->toBe(50);

    ExactMcpServer::tool(ExactApiReadTool::class, [
        'entity' => 'get_accounts',
        'options' => ['top' => 9999],
    ])->assertOk()->assertSee('a1');

    expect($spy->received['top'])->toBe(200);
});

it('dispatches single getters when an id is provided', function () {
    config()->set('exactonline-laravel-api.actions.get_account', FakeMcpAccountAction::class);

    ExactMcpServer::tool(ExactApiReadTool::class, [
        'entity' => 'get_account',
        'id' => 'guid-123',
    ])->assertOk()->assertSee('guid-123');
});

it('routes single reads with select through the collection getter and unwraps one record', function () {
    $spy = new FakeMcpGLAccountsAction;

    $this->app->instance(FakeMcpGLAccountsAction::class, $spy);
    config()->set('exactonline-laravel-api.actions.get_gl_account', FakeMcpGLAccountAction::class);
    config()->set('exactonline-laravel-api.actions.get_gl_accounts', FakeMcpGLAccountsAction::class);

    ExactMcpServer::tool(ExactApiReadTool::class, [
        'entity' => 'get_gl_account',
        'id' => '81ae9335-3301-4bb4-962b-759197279d5b',
        'options' => ['select' => ['ID', 'Code']],
    ])->assertOk()->assertSee('"Code":"4000"');

    expect($spy->received)->toMatchArray([
        'select' => ['ID', 'Code'],
        'filter' => "ID eq guid'81ae9335-3301-4bb4-962b-759197279d5b'",
        'top' => 1,
    ]);
});

it('normalizes empty single getter results to null', function () {
    config()->set('exactonline-laravel-api.actions.get_account', FakeMcpEmptyAccountAction::class);

    ExactMcpServer::tool(ExactApiReadTool::class, [
        'entity' => 'get_account',
        'id' => '00000000-0000-0000-0000-000000000000',
    ])->assertOk()->assertSee('"record":null');
});

it('returns sanitized errors when an action throws a throwable', function () {
    config()->set('exactonline-laravel-api.actions.get_accounts', FakeMcpThrowableAccountsAction::class);

    ExactMcpServer::tool(ExactApiReadTool::class, [
        'entity' => 'get_accounts',
    ])->assertHasErrors()->assertSee('Exact API error.')->assertDontSee('/private/path');
});

it('rejects non-readable action names', function () {
    ExactMcpServer::tool(ExactApiReadTool::class, [
        'entity' => 'sync_account',
    ])->assertHasErrors();
});
