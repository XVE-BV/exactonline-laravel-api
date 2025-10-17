<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\AcquireAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

uses(RefreshDatabase::class);

class TestableAcquireAccessTokenAction extends AcquireAccessTokenAction
{
    protected function exchangeCodeForTokens(ExactConnection $connection, string $code): array
    {
        // Return deterministic tokens for testing
        return [
            'access_token' => 'new-access-'.$code,
            'refresh_token' => 'new-refresh-'.$code,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ];
    }
}

it('exchanges authorization code and stores tokens', function () {
    // Arrange
    $connection = ExactConnection::create([
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect_url' => 'https://app.test/callback',
        'base_url' => 'https://start.exactonline.nl',
        'is_active' => false,
    ]);

    $action = new TestableAcquireAccessTokenAction();

    // Act
    $tokens = $action->execute($connection, 'abc123authorization-longer');

    $connection->refresh();

    // Assert
    expect($tokens['access_token'])->toBe('new-access-abc123authorization-longer');
    expect($tokens['refresh_token'])->toBe('new-refresh-abc123authorization-longer');
    expect($connection->getDecryptedAccessToken())->toBe('new-access-abc123authorization-longer');
    expect($connection->getDecryptedRefreshToken())->toBe('new-refresh-abc123authorization-longer');
    expect($connection->is_active)->toBeTrue();
});
