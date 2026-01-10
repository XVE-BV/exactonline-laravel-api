<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Payment;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\CheckRateLimitAction;
use Skylence\ExactonlineLaravelApi\Actions\RateLimit\TrackRateLimitUsageAction;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class UpdatePaymentAction
{
    /**
     * Update an existing payment in Exact Online
     *
     * Note: According to the Exact Online API, Payments only support GET and PUT operations.
     * POST (create) and DELETE operations are not supported.
     *
     * @param  ExactConnection  $connection  The Exact Online connection
     * @param  string  $paymentId  The payment ID (GUID) to update
     * @param  array{
     *     Account?: string|null,
     *     AccountBankAccountID?: string|null,
     *     AccountBankAccountNumber?: string|null,
     *     AccountCode?: string|null,
     *     AccountContact?: string|null,
     *     AccountContactName?: string|null,
     *     AccountName?: string|null,
     *     AmountDC?: float|null,
     *     AmountDiscountDC?: float|null,
     *     AmountDiscountFC?: float|null,
     *     AmountFC?: float|null,
     *     BankAccountID?: string|null,
     *     BankAccountNumber?: string|null,
     *     CashflowTransactionBatchCode?: string|null,
     *     Created?: string|null,
     *     Creator?: string|null,
     *     CreatorFullName?: string|null,
     *     Currency?: string|null,
     *     Description?: string|null,
     *     DiscountDueDate?: string|null,
     *     Division?: int|null,
     *     Document?: string|null,
     *     DocumentNumber?: int|null,
     *     DocumentSubject?: string|null,
     *     DueDate?: string|null,
     *     EndDate?: string|null,
     *     EndPeriod?: int|null,
     *     EndYear?: int|null,
     *     EntryDate?: string|null,
     *     EntryID?: string|null,
     *     EntryNumber?: int|null,
     *     GLAccount?: string|null,
     *     GLAccountCode?: string|null,
     *     GLAccountDescription?: string|null,
     *     InvoiceDate?: string|null,
     *     InvoiceNumber?: int|null,
     *     IsBatchBooking?: int|null,
     *     Journal?: string|null,
     *     JournalDescription?: string|null,
     *     Modified?: string|null,
     *     Modifier?: string|null,
     *     ModifierFullName?: string|null,
     *     PaymentBatchNumber?: int|null,
     *     PaymentCondition?: string|null,
     *     PaymentConditionDescription?: string|null,
     *     PaymentDays?: int|null,
     *     PaymentDaysDiscount?: int|null,
     *     PaymentDiscountPercentage?: float|null,
     *     PaymentMethod?: string|null,
     *     PaymentReference?: string|null,
     *     PaymentSelected?: string|null,
     *     PaymentSelector?: string|null,
     *     PaymentSelectorFullName?: string|null,
     *     RateFC?: float|null,
     *     Source?: int|null,
     *     Status?: int|null,
     *     TransactionAmountDC?: float|null,
     *     TransactionAmountFC?: float|null,
     *     TransactionDueDate?: string|null,
     *     TransactionEntryID?: string|null,
     *     TransactionID?: string|null,
     *     TransactionIsReversal?: bool|null,
     *     TransactionReportingPeriod?: int|null,
     *     TransactionReportingYear?: int|null,
     *     TransactionStatus?: int|null,
     *     TransactionType?: int|null,
     *     YourRef?: string|null
     * }  $data  Payment data to update
     * @return array<string, mixed> The updated payment data
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $paymentId, array $data): array
    {
        // Validate update data
        $this->validateUpdateData($data);

        // Ensure we have a valid access token
        $this->ensureValidToken($connection);

        // Check rate limits before making the request
        $this->checkRateLimit($connection);

        try {
            // Get the picqer connection
            $picqerConnection = $connection->getPicqerConnection();

            // Create Payment instance
            $payment = new Payment($picqerConnection);

            // Find the existing payment
            $existingPayment = $payment->find($paymentId);

            if ($existingPayment === null) {
                throw new ConnectionException("Payment with ID {$paymentId} not found");
            }

            // Update payment properties
            foreach ($data as $key => $value) {
                $existingPayment->{$key} = $value;
            }

            // Save the updated payment
            $existingPayment->save();

            // Track rate limit usage after the request
            $this->trackRateLimitUsage($connection, $picqerConnection);

            Log::info('Updated payment in Exact Online', [
                'connection_id' => $connection->id,
                'payment_id' => $paymentId,
                'updated_fields' => array_keys($data),
            ]);

            // Return the updated payment data
            return $existingPayment->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to update payment in Exact Online', [
                'connection_id' => $connection->id,
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new ConnectionException(
                'Failed to update payment: '.$e->getMessage(),
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
                'No data provided for payment update'
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
