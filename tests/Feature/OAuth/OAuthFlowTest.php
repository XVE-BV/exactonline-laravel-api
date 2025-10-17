<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config as LaravelConfig;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\AcquireAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

uses(RefreshDatabase::class);

it('redirects to failure url on state mismatch', function () {
    // Act
    $response = $this->withSession(['exact_oauth_state' => 'good-state'])
        ->get(route('exact.oauth.callback', [
            'code' => 'abc',
            'state' => 'bad-state',
        ]));

    // Assert
    $response->assertRedirect(config('exactonline-laravel-api.oauth.failure_url'));
    $response->assertSessionHas('exact_oauth_error');
});

class StubAcquireAccessTokenAction extends AcquireAccessTokenAction
{
    public function execute(ExactConnection $connection, string $authorizationCode): array
    {
        $tokens = [
            'access_token' => 'stub-access',
            'refresh_token' => 'stub-refresh',
            'expires_at' => now()->addMinutes(10)->timestamp,
        ];

        // store directly to simulate success
        $connection->update([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_expires_at' => $tokens['expires_at'],
            'refresh_token_expires_at' => now()->addDays(30)->timestamp,
            'is_active' => true,
        ]);

        return $tokens;
    }
}

it('handles successful callback and redirects to success url', function () {
    // Arrange: configure stub action
    LaravelConfig::set('exactonline-laravel-api.actions.acquire_access_token', StubAcquireAccessTokenAction::class);

    $connection = ExactConnection::create([
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect_url' => 'https://app.test/callback',
        'base_url' => 'https://start.exactonline.nl',
        'is_active' => false,
    ]);

    // Act
    $response = $this->withSession([
        'exact_oauth_state' => 'state-123',
        'exact_oauth_connection_id' => $connection->id,
    ])->get(route('exact.oauth.callback', [
        'code' => 'the-code',
        'state' => 'state-123',
    ]));

    // Assert
    $response->assertRedirect(config('exactonline-laravel-api.oauth.success_url'));
    $response->assertSessionHas('exact_connection_id', $connection->id);
});
