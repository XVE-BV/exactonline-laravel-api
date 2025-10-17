<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\Connection;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\ApiException;
use Skylence\ExactonlineLaravelApi\Exceptions\ConnectionException;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class SwitchDivisionAction
{
    /**
     * Switch to a different division (administration) in Exact Online
     *
     * This action updates the active division for a connection and verifies
     * that the user has access to the new division.
     *
     * @param  int|string  $divisionId  The division ID to switch to
     * @param  bool  $verifyAccess  Whether to verify access to the division
     *
     * @throws ConnectionException
     */
    public function execute(
        ExactConnection $connection,
        int|string $divisionId,
        bool $verifyAccess = true
    ): ExactConnection {
        // Validate connection is active
        if (! $connection->is_active) {
            throw ConnectionException::inactive((string) $connection->id);
        }

        // Convert division ID to string (Exact Online uses integer division IDs)
        $divisionId = (string) $divisionId;

        // Check if already using this division
        if ($connection->division === $divisionId) {
            Log::info('Already using requested division', [
                'connection_id' => $connection->id,
                'division' => $divisionId,
            ]);

            return $connection;
        }

        // Verify access to the new division if requested
        if ($verifyAccess) {
            $this->verifyDivisionAccess($connection, $divisionId);
        }

        // Update the division
        $oldDivision = $connection->division;
        $connection->update(['division' => $divisionId]);

        // Update metadata to track division changes
        $metadata = $connection->metadata ?? [];
        $metadata['division_history'] = $metadata['division_history'] ?? [];
        $metadata['division_history'][] = [
            'from' => $oldDivision,
            'to' => $divisionId,
            'changed_at' => now()->toIso8601String(),
        ];
        $connection->update(['metadata' => $metadata]);

        Log::info('Division switched successfully', [
            'connection_id' => $connection->id,
            'old_division' => $oldDivision,
            'new_division' => $divisionId,
        ]);

        return $connection->fresh();
    }

    /**
     * Get list of available divisions for the connection
     *
     * @return array<array{
     *     id: int,
     *     code: string,
     *     description: string,
     *     current: bool
     * }>
     *
     * @throws ConnectionException
     */
    public function getAvailableDivisions(ExactConnection $connection): array
    {
        if (! $connection->is_active) {
            throw ConnectionException::inactive((string) $connection->id);
        }

        try {
            $picqerConnection = $connection->getPicqerConnection();

            // Get list of divisions
            $divisionsApi = new \Picqer\Financials\Exact\Division($picqerConnection);
            $divisions = $divisionsApi->get();

            $availableDivisions = [];

            foreach ($divisions as $division) {
                $availableDivisions[] = [
                    'id' => $division->Code,
                    'code' => $division->Code,
                    'description' => $division->Description ?? '',
                    'current' => (string) $division->Code === $connection->division,
                ];
            }

            return $availableDivisions;

        } catch (ApiException $e) {
            Log::error('Failed to get available divisions', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw ConnectionException::apiError(
                'Failed to retrieve divisions: '.$e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * Verify access to a division
     *
     * @throws ConnectionException
     */
    protected function verifyDivisionAccess(ExactConnection $connection, string $divisionId): void
    {
        try {
            $picqerConnection = $connection->getPicqerConnection();

            // Temporarily set the division to test access
            $originalDivision = $picqerConnection->getDivision();
            $picqerConnection->setDivision($divisionId);

            try {
                // Try to access division-specific data
                $division = new \Picqer\Financials\Exact\Division($picqerConnection);
                $divisionData = $division->find($divisionId);

                if (! $divisionData) {
                    throw ConnectionException::divisionNotAccessible($divisionId);
                }

                // Also try a simple API call to ensure full access
                $accounts = new \Picqer\Financials\Exact\Account($picqerConnection);
                $accounts->get(); // Fetch accounts to verify access

            } finally {
                // Restore original division
                if ($originalDivision) {
                    $picqerConnection->setDivision($originalDivision);
                }
            }

        } catch (ApiException $e) {
            Log::error('Division access verification failed', [
                'connection_id' => $connection->id,
                'division' => $divisionId,
                'error' => $e->getMessage(),
            ]);

            // Check for specific error codes
            if (str_contains($e->getMessage(), '403') ||
                str_contains($e->getMessage(), 'Forbidden') ||
                str_contains($e->getMessage(), 'division')) {
                throw ConnectionException::divisionNotAccessible($divisionId);
            }

            throw ConnectionException::apiError(
                'Failed to verify division access: '.$e->getMessage(),
                $e->getCode()
            );

        } catch (\Exception $e) {
            if ($e instanceof ConnectionException) {
                throw $e;
            }

            Log::error('Unexpected error during division verification', [
                'connection_id' => $connection->id,
                'division' => $divisionId,
                'error' => $e->getMessage(),
            ]);

            throw ConnectionException::divisionNotAccessible($divisionId);
        }
    }
}
