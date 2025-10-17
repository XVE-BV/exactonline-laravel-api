<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\StoreTokensAction;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

uses(RefreshDatabase::class);

it('stores tokens with defaults and encrypts them', function () {
    // Arrange: create a minimal ExactConnection
    $connection = ExactConnection::create([
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect_url' => 'https://app.test/callback',
        'base_url' => 'https://start.exactonline.nl',
        'is_active' => false,
    ]);

    $action = new StoreTokensAction();

    $now = now();
    \Illuminate\Support\Carbon::setTestNow($now);

    // Act
    $updated = $action->execute($connection, [
        'access_token' => 'access-123',
        'refresh_token' => 'refresh-456',
        // no expires_at provided to use default 10 minutes
    ]);

    // Assert
    expect($updated->id)->toBe($connection->id);
    expect($updated->getDecryptedAccessToken())->toBe('access-123');
    expect($updated->getDecryptedRefreshToken())->toBe('refresh-456');
    expect($updated->token_expires_at)->toBe($now->copy()->addMinutes(10)->timestamp);
    expect($updated->refresh_token_expires_at)->toBe($now->copy()->addDays(30)->timestamp);
    expect($updated->is_active)->toBeTrue();
});
