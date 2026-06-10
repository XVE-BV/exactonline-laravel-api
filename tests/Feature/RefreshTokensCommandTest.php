<?php

declare(strict_types=1);

use XVE\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

it('skips connections whose refresh token is not near expiry', function () {
    ExactConnection::factory()->create([
        'is_active' => true,
        'refresh_token' => 'valid-refresh-token',
        'refresh_token_expires_at' => now()->addDays(20)->timestamp,
    ]);

    $action = $this->mock(RefreshAccessTokenAction::class);
    $action->shouldNotReceive('execute');

    $this->artisan('exact:refresh-tokens')->assertSuccessful();
});

it('refreshes connections whose refresh token is within the buffer', function () {
    ExactConnection::factory()->create([
        'is_active' => true,
        'refresh_token' => 'valid-refresh-token',
        'refresh_token_expires_at' => now()->addDays(3)->timestamp,
    ]);

    $action = $this->mock(RefreshAccessTokenAction::class);
    $action->shouldReceive('execute')->once();

    $this->artisan('exact:refresh-tokens')->assertSuccessful();
});

it('refreshes connections that have no refresh token expiry recorded', function () {
    ExactConnection::factory()->create([
        'is_active' => true,
        'refresh_token' => 'valid-refresh-token',
        'refresh_token_expires_at' => null,
    ]);

    $action = $this->mock(RefreshAccessTokenAction::class);
    $action->shouldReceive('execute')->once();

    $this->artisan('exact:refresh-tokens')->assertSuccessful();
});
