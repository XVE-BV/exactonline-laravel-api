<?php

declare(strict_types=1);

use XVE\ExactonlineLaravelApi\Mcp\Support\SecretScrubber;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

it('scrubs exact credential fields in full mode', function () {
    $scrubbed = (new SecretScrubber)->scrubFull([
        'access_token' => 'access',
        'refresh_token' => 'refresh',
        'client_secret' => 'secret',
        'client_id' => 'client',
        'tenant_id' => 'tenant',
        'webhook_secret' => 'webhook',
        'name' => 'kept',
    ]);

    expect($scrubbed)
        ->not->toHaveKeys([
            'access_token',
            'refresh_token',
            'client_secret',
            'client_id',
            'tenant_id',
            'webhook_secret',
        ])
        ->and($scrubbed['name'])->toBe('kept');
});

it('recursively scrubs sensitive metadata keys in full mode', function () {
    $scrubbed = (new SecretScrubber)->scrubFull([
        'metadata' => [
            'api_key' => 'key',
            'password' => 'password',
            'note' => 'keep',
        ],
    ]);

    expect($scrubbed['metadata'])
        ->not->toHaveKeys(['api_key', 'password'])
        ->and($scrubbed['metadata']['note'])->toBe('keep');
});

it('keeps safe token timestamp fields in full mode', function () {
    $scrubbed = (new SecretScrubber)->scrubFull([
        'token_expires_at' => 123,
        'refresh_token_expires_at' => 456,
        'last_token_refresh_at' => '2026-06-10T10:00:00+00:00',
    ]);

    expect($scrubbed)
        ->toHaveKey('token_expires_at', 123)
        ->toHaveKey('refresh_token_expires_at', 456)
        ->toHaveKey('last_token_refresh_at', '2026-06-10T10:00:00+00:00');
});

it('scrubs only exact credential fields in known-field mode', function () {
    $scrubbed = (new SecretScrubber)->scrubKnownFields([
        'access_token' => 'removed',
        'PaymentToken' => 'kept',
    ]);

    expect($scrubbed)
        ->not->toHaveKey('access_token')
        ->and($scrubbed['PaymentToken'])->toBe('kept');
});

it('scrubs secret keys from a serialized exact connection', function () {
    $connection = ExactConnection::factory()->create([
        'access_token' => 'access',
        'refresh_token' => 'refresh',
        'client_secret' => 'secret',
        'client_id' => 'client',
        'tenant_id' => 'tenant',
        'metadata' => ['api_key' => 'metadata-key', 'note' => 'keep'],
    ]);

    $scrubbed = (new SecretScrubber)->scrubFull($connection->toArray());

    expect($scrubbed)
        ->not->toHaveKeys([
            'access_token',
            'refresh_token',
            'client_secret',
            'client_id',
            'tenant_id',
            'webhook_secret',
        ])
        ->and($scrubbed['metadata'])->not->toHaveKey('api_key')
        ->and($scrubbed['metadata']['note'])->toBe('keep');
});
