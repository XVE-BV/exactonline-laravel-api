<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RevokeTokensAction;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

uses(RefreshDatabase::class);

it('revokes tokens locally and deactivates the connection', function () {
    // Arrange
    $connection = ExactConnection::create([
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect_url' => 'https://app.test/callback',
        'base_url' => 'https://start.exactonline.nl',
        'is_active' => true,
        'access_token' => 'access-xyz',
        'refresh_token' => 'refresh-xyz',
        'token_expires_at' => now()->addMinutes(5)->timestamp,
        'refresh_token_expires_at' => now()->addDays(10)->timestamp,
    ]);

    $action = new RevokeTokensAction;

    // Act (skip notifying Exact Online to keep test deterministic)
    $action->execute($connection, false);

    $connection->refresh();

    // Assert
    expect($connection->access_token)->toBeNull();
    expect($connection->refresh_token)->toBeNull();
    expect($connection->token_expires_at)->toBeNull();
    expect($connection->refresh_token_expires_at)->toBeNull();
    expect($connection->is_active)->toBeFalse();
});
