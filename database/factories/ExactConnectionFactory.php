<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

/**
 * @extends Factory<ExactConnection>
 */
class ExactConnectionFactory extends Factory
{
    protected $model = ExactConnection::class;

    public function definition(): array
    {
        return [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_url' => 'https://example.com/callback',
            'base_url' => 'https://start.exactonline.nl',
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'token_expires_at' => now()->addHour()->getTimestamp(),
            'is_active' => true,
            'tenant_id' => null,
            'division' => null,
            'name' => null,
            'metadata' => null,
        ];
    }
}
