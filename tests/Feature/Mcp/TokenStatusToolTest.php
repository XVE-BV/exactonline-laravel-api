<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Laravel\Mcp\Server\McpServiceProvider;
use XVE\ExactonlineLaravelApi\Events\RefreshTokenExpiringSoon;
use XVE\ExactonlineLaravelApi\Mcp\ExactMcpServer;
use XVE\ExactonlineLaravelApi\Mcp\Tools\TokenStatusTool;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

it('reports refresh token expiry status without dispatching expiry events', function () {
    $this->app->register(McpServiceProvider::class);

    Event::fake();

    $connection = ExactConnection::factory()->create([
        'name' => 'Expiring refresh token',
        'is_active' => true,
        'access_token' => 'access',
        'refresh_token' => 'refresh',
        'token_expires_at' => now()->addHour()->timestamp,
        'refresh_token_expires_at' => now()->addDays(3)->timestamp,
    ]);

    ExactMcpServer::tool(TokenStatusTool::class, [
        'connection_id' => $connection->id,
    ])->assertOk()->assertSee('"expiring_soon":true');

    Event::assertNotDispatched(RefreshTokenExpiringSoon::class);
});
