<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Account;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use Skylence\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class UpdateAccountAction
{
    use ValidatesPayload;

    /**
     * Update an existing account in Exact Online
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $accountId  The account ID (GUID) to update
     * @param  array{
     *     Name?: string,
     *     Code?: string|null,
     *     SearchCode?: string|null,
     *     Status?: string|null,
     *     Email?: string|null,
     *     Phone?: string|null,
     *     Website?: string|null,
     *     VATNumber?: string|null,
     *     ChamberOfCommerce?: string|null,
     *     City?: string|null,
     *     Country?: string|null,
     *     AddressLine1?: string|null,
     *     AddressLine2?: string|null,
     *     AddressLine3?: string|null,
     *     Postcode?: string|null,
     *     State?: string|null,
     *     StartDate?: string|null,
     *     EndDate?: string|null,
     *     CreditLinePurchase?: float|null,
     *     CreditLineSales?: float|null,
     *     DiscountPurchase?: float|null,
     *     DiscountSales?: float|null,
     *     Remarks?: string|null,
     *     SalesCurrency?: string|null,
     *     SalesVATCode?: string|null,
     *     IsSupplier?: bool|null,
     *     IsCustomer?: bool|null,
     *     IsSales?: bool|null
     * }  $data  Account data to update
     * @return array<string, mixed> The updated account data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $accountId, array $data): array
    {
        // Validate against schema
        $this->validateUpdatePayload('Account', $data);

        // Validate update data
        $this->validateUpdateData($data);

        // Ensure we have a valid access token
        $this->ensureValidToken($connection);

        // Check rate limits before making the request
        $this->checkRateLimit($connection);

        try {
            // Get the picqer connection
            $picqerConnection = $connection->getPicqerConnection();

            // Create Account instance
            $account = new Account($picqerConnection);

            // Find the existing account
            $existingAccount = $account->find($accountId);

            if ($existingAccount === null) {
                throw new ConnectionException("Account with ID {$accountId} not found");
            }

            // Update account properties
            foreach ($data as $key => $value) {
                $existingAccount->{$key} = $value;
            }

            // Save the updated account
            $existingAccount->save();

            // Track rate limit usage after the request
            $this->trackRateLimitUsage($connection, $picqerConnection);

            Log::info('Updated account in Exact Online', [
                'connection_id' => $connection->id,
                'account_id' => $accountId,
                'updated_fields' => array_keys($data),
            ]);

            // Return the updated account data
            return $existingAccount->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to update account in Exact Online', [
                'connection_id' => $connection->id,
                'account_id' => $accountId,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new ConnectionException(
                'Failed to update account: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Validate update data
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ConnectionException
     */
    protected function validateUpdateData(array $data): void
    {
        // Ensure we have at least one field to update
        if (empty($data)) {
            throw ConnectionException::invalidConfiguration(
                'No data provided for account update'
            );
        }

        // Validate email format if provided
        if (isset($data['Email']) && $data['Email'] !== null && ! filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
            throw ConnectionException::invalidConfiguration(
                'Invalid email format provided'
            );
        }

        // Validate website format if provided
        if (isset($data['Website']) && $data['Website'] !== null && ! filter_var($data['Website'], FILTER_VALIDATE_URL)) {
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
        return $connection->token_expires_at < (now()->timestamp + 540);
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
    protected function trackRateLimitUsage(ExactConnection $connection, \Picqer\Financials\Exact\Connection $picqerConnection): void
    {
        $trackRateLimitAction = Config::getAction(
            'track_rate_limit_usage',
            TrackRateLimitUsageAction::class
        );
        $trackRateLimitAction->execute($connection, $picqerConnection);
    }
}
