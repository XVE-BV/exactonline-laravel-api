<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Account;
use Picqer\Financials\Exact\Connection;
use XVE\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use XVE\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use XVE\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use XVE\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;
use XVE\ExactonlineLaravelApi\Support\Config;

class CreateAccountAction
{
    use ValidatesPayload;

    /**
     * Create a new account in Exact Online
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  array<string, mixed>  $data  The entity data
     * @return array<string, mixed> The created account data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, array $data): array
    {
        // Validate against schema
        $this->validateCreatePayload('Account', $data);

        // Validate required fields
        $this->validateAccountData($data);

        // Ensure we have a valid access token
        $this->ensureValidToken($connection);

        // Check rate limits before making the request
        $this->checkRateLimit($connection);

        try {
            // Get the picqer connection
            $picqerConnection = $connection->getPicqerConnection();

            // Create Account instance
            $account = new Account($picqerConnection);

            // Set account properties
            foreach ($data as $key => $value) {
                $account->{$key} = $value;
            }

            // Save the account
            $account->save();

            // Track rate limit usage after the request
            $this->trackRateLimitUsage($connection, $picqerConnection);

            Log::info('Created account in Exact Online', [
                'connection_id' => $connection->id,
                'account_id' => $account->ID,
                'account_name' => $account->Name,
                'account_code' => $account->Code ?? 'N/A',
            ]);

            // Return the created account data
            return $account->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to create account in Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new ConnectionException(
                'Failed to create account: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Validate required account data
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateAccountData(array $data): void
    {
        // Name is the only required field for accounts
        if (empty($data['Name'])) {
            throw ConnectionException::invalidConfiguration(
                'Account name is required'
            );
        }

        // Validate email format if provided
        if (! empty($data['Email']) && ! filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
            throw ConnectionException::invalidConfiguration(
                'Invalid email format provided'
            );
        }

        // Validate website format if provided
        if (! empty($data['Website']) && ! filter_var($data['Website'], FILTER_VALIDATE_URL)) {
            throw ConnectionException::invalidConfiguration(
                'Invalid website URL format provided'
            );
        }
    }

    /**
     * Ensure the connection has a valid access token
     */
    protected function ensureValidToken(ExactConnection $connection): void
    {
        if ($this->tokenNeedsRefresh($connection)) {
            $refreshAction = Config::getAction(
                'refresh_access_token',
                RefreshAccessTokenAction::class
            );
            $refreshAction->execute($connection);

            // Refresh the connection to get updated tokens
            $connection->refresh();
        }
    }

    /**
     * Check if token needs refresh (proactive at 9 minutes)
     */
    protected function tokenNeedsRefresh(ExactConnection $connection): bool
    {
        if (empty($connection->token_expires_at)) {
            return true;
        }

        // Refresh proactively at 9 minutes (540 seconds before expiry)
        return $connection->token_expires_at < (now()->getTimestamp() + 540);
    }

    /**
     * Check rate limits before making the API request
     */
    protected function checkRateLimit(ExactConnection $connection): void
    {
        $checkRateLimitAction = Config::getAction(
            'check_rate_limit',
            CheckRateLimitAction::class
        );
        $checkRateLimitAction->execute($connection);
    }

    /**
     * Track rate limit usage after the API request
     */
    protected function trackRateLimitUsage(ExactConnection $connection, Connection $picqerConnection): void
    {
        $trackRateLimitAction = Config::getAction(
            'track_rate_limit_usage',
            TrackRateLimitUsageAction::class
        );
        $trackRateLimitAction->execute($connection, $picqerConnection);
    }
}
