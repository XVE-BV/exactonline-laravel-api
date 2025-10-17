<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\Connection;

use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class CreateConnectionAction
{
    /**
     * Create a new Exact Online connection
     *
     * This action creates a new connection record with the provided configuration.
     * The connection will be inactive until tokens are acquired via OAuth.
     *
     * @param array{
     *     user_id?: int|null,
     *     tenant_id?: string|null,
     *     name?: string,
     *     division?: string|null,
     *     client_id?: string|null,
     *     client_secret?: string|null,
     *     redirect_url?: string|null,
     *     base_url?: string
     * } $data
     * @return ExactConnection
     * @throws ConnectionException
     */
    public function execute(array $data): ExactConnection
    {
        // Validate required configuration
        $this->validateConnectionData($data);

        // Prepare connection data with defaults
        $connectionData = $this->prepareConnectionData($data);

        try {
            // Create the connection record
            $connection = ExactConnection::create($connectionData);

            Log::info('Exact Online connection created', [
                'connection_id' => $connection->id,
                'user_id' => $connection->user_id,
                'tenant_id' => $connection->tenant_id,
                'name' => $connection->name,
            ]);

            return $connection;

        } catch (\Exception $e) {
            Log::error('Failed to create Exact Online connection', [
                'error' => $e->getMessage(),
                'data' => $connectionData,
            ]);

            throw ConnectionException::invalidConfiguration(
                'Failed to create connection: ' . $e->getMessage()
            );
        }
    }

    /**
     * Validate connection data
     *
     * @param array<string, mixed> $data
     * @return void
     * @throws ConnectionException
     */
    protected function validateConnectionData(array $data): void
    {
        // Get OAuth credentials from config if not provided
        $clientId = $data['client_id'] ?? Config::getClientId();
        $clientSecret = $data['client_secret'] ?? Config::getClientSecret();

        if (empty($clientId) || empty($clientSecret)) {
            throw ConnectionException::invalidConfiguration(
                'OAuth client ID and secret are required. ' .
                'Please provide them in the data array or set them in your configuration.'
            );
        }

        // Validate redirect URL
        $redirectUrl = $data['redirect_url'] ?? Config::getRedirectUrl();
        if (empty($redirectUrl)) {
            throw ConnectionException::invalidConfiguration(
                'OAuth redirect URL is required. ' .
                'Please provide it in the data array or set it in your configuration.'
            );
        }

        // Validate base URL if provided
        if (isset($data['base_url']) && ! filter_var($data['base_url'], FILTER_VALIDATE_URL)) {
            throw ConnectionException::invalidConfiguration(
                'Invalid base URL provided. It must be a valid URL.'
            );
        }
    }

    /**
     * Prepare connection data with defaults
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function prepareConnectionData(array $data): array
    {
        // Generate default name if not provided
        $name = $data['name'] ?? $this->generateConnectionName($data);

        return [
            'user_id' => $data['user_id'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? null,
            'name' => $name,
            'division' => $data['division'] ?? config('exactonline-laravel-api.connection.division'),
            'client_id' => $data['client_id'] ?? Config::getClientId(),
            'client_secret' => $data['client_secret'] ?? Config::getClientSecret(),
            'redirect_url' => $data['redirect_url'] ?? Config::getRedirectUrl(),
            'base_url' => $data['base_url'] ?? config('exactonline-laravel-api.connection.base_url', 'https://start.exactonline.nl'),
            'is_active' => false, // Connections start as inactive until OAuth is completed
            'metadata' => $data['metadata'] ?? [],
        ];
    }

    /**
     * Generate a default connection name
     *
     * @param array<string, mixed> $data
     * @return string
     */
    protected function generateConnectionName(array $data): string
    {
        $parts = ['Exact Online'];

        // Add user context if available
        if (isset($data['user_id'])) {
            $parts[] = "User {$data['user_id']}";
        }

        // Add tenant context if available
        if (isset($data['tenant_id'])) {
            $parts[] = "Tenant {$data['tenant_id']}";
        }

        // Add timestamp
        $parts[] = now()->format('Y-m-d H:i');

        return implode(' - ', $parts);
    }
}
