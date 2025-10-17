<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\Connection;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\ApiException;
use Skylence\ExactonlineLaravelApi\Actions\OAuth\RefreshAccessTokenAction;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;
use Skylence\ExactonlineLaravelApi\Support\Config;

class ValidateConnectionAction
{
    /**
     * Validate an Exact Online connection
     *
     * This action checks if a connection is properly configured and can
     * successfully communicate with the Exact Online API.
     *
     * @param  bool  $refreshTokenIfNeeded  Whether to refresh token if it's expired
     * @return array{
     *     valid: bool,
     *     active: bool,
     *     token_valid: bool,
     *     api_reachable: bool,
     *     division_accessible: bool,
     *     errors: array<string>
     * }
     */
    public function execute(ExactConnection $connection, bool $refreshTokenIfNeeded = true): array
    {
        $result = [
            'valid' => false,
            'active' => $connection->is_active,
            'token_valid' => false,
            'api_reachable' => false,
            'division_accessible' => false,
            'errors' => [],
        ];

        // Check if connection is active
        if (! $connection->is_active) {
            $result['errors'][] = 'Connection is not active';
        }

        // Check if tokens exist
        if (! $connection->access_token || ! $connection->refresh_token) {
            $result['errors'][] = 'No tokens available - OAuth authorization required';

            return $result;
        }

        // Check token validity
        $tokenValidation = $this->validateTokens($connection, $refreshTokenIfNeeded);
        $result['token_valid'] = $tokenValidation['valid'];

        if (! $tokenValidation['valid']) {
            $result['errors'][] = $tokenValidation['error'];

            // If tokens are invalid and we can't refresh, stop here
            if (! $refreshTokenIfNeeded || ! $tokenValidation['refreshed']) {
                return $result;
            }
        }

        // Test API connectivity
        $apiTest = $this->testApiConnectivity($connection);
        $result['api_reachable'] = $apiTest['reachable'];

        if (! $apiTest['reachable']) {
            $result['errors'][] = $apiTest['error'];

            return $result;
        }

        // Verify division access
        $divisionTest = $this->verifyDivisionAccess($connection);
        $result['division_accessible'] = $divisionTest['accessible'];

        if (! $divisionTest['accessible']) {
            $result['errors'][] = $divisionTest['error'];
        }

        // Connection is valid if all checks pass
        $result['valid'] = $result['active'] &&
                          $result['token_valid'] &&
                          $result['api_reachable'] &&
                          $result['division_accessible'];

        Log::info('Connection validation completed', [
            'connection_id' => $connection->id,
            'valid' => $result['valid'],
            'errors' => $result['errors'],
        ]);

        return $result;
    }

    /**
     * Validate tokens and refresh if needed
     *
     * @return array{valid: bool, refreshed: bool, error: string|null}
     */
    protected function validateTokens(ExactConnection $connection, bool $refreshIfNeeded): array
    {
        // Check if token needs refresh
        if ($connection->tokenNeedsRefresh()) {
            if (! $refreshIfNeeded) {
                return [
                    'valid' => false,
                    'refreshed' => false,
                    'error' => 'Access token expired or expiring soon',
                ];
            }

            try {
                // Attempt to refresh the token
                $refreshAction = Config::getAction(
                    'refresh_access_token',
                    RefreshAccessTokenAction::class
                );

                $refreshAction->execute($connection);

                return [
                    'valid' => true,
                    'refreshed' => true,
                    'error' => null,
                ];

            } catch (\Exception $e) {
                Log::error('Token refresh failed during validation', [
                    'connection_id' => $connection->id,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'valid' => false,
                    'refreshed' => false,
                    'error' => 'Failed to refresh access token: '.$e->getMessage(),
                ];
            }
        }

        // Check if refresh token is expiring soon
        if ($connection->refreshTokenExpiringSoon(7)) {
            Log::warning('Refresh token expiring soon', [
                'connection_id' => $connection->id,
                'expires_at' => $connection->refresh_token_expires_at,
            ]);
        }

        return [
            'valid' => true,
            'refreshed' => false,
            'error' => null,
        ];
    }

    /**
     * Test API connectivity
     *
     * @return array{reachable: bool, error: string|null}
     */
    protected function testApiConnectivity(ExactConnection $connection): array
    {
        try {
            $picqerConnection = $connection->getPicqerConnection();

            // Try to get the current user info (lightweight API call)
            $me = new \Picqer\Financials\Exact\Me($picqerConnection);
            $currentUser = $me->get();

            if (empty($currentUser)) {
                return [
                    'reachable' => false,
                    'error' => 'Unable to retrieve user information from Exact Online',
                ];
            }

            // Store division if we don't have one yet
            if (! $connection->division && isset($currentUser[0]->CurrentDivision)) {
                $connection->update(['division' => $currentUser[0]->CurrentDivision]);
            }

            return [
                'reachable' => true,
                'error' => null,
            ];

        } catch (ApiException $e) {
            Log::error('Exact Online API error during validation', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'reachable' => false,
                'error' => 'Exact Online API error: '.$e->getMessage(),
            ];

        } catch (\Exception $e) {
            Log::error('Unexpected error during API connectivity test', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'reachable' => false,
                'error' => 'Failed to connect to Exact Online: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Verify division access
     *
     * @return array{accessible: bool, error: string|null}
     */
    protected function verifyDivisionAccess(ExactConnection $connection): array
    {
        // If no division is set, we can't verify access
        if (! $connection->division) {
            return [
                'accessible' => false,
                'error' => 'No division configured for this connection',
            ];
        }

        try {
            $picqerConnection = $connection->getPicqerConnection();

            // Set the division
            $picqerConnection->setDivision($connection->division);

            // Try to access division-specific data (SystemDivisions is a good test)
            $division = new \Picqer\Financials\Exact\Division($picqerConnection);
            $divisionData = $division->find($connection->division);

            if (! $divisionData) {
                return [
                    'accessible' => false,
                    'error' => "Division '{$connection->division}' not found or not accessible",
                ];
            }

            return [
                'accessible' => true,
                'error' => null,
            ];

        } catch (ApiException $e) {
            // Check for specific error codes
            if (str_contains($e->getMessage(), '403') || str_contains($e->getMessage(), 'Forbidden')) {
                return [
                    'accessible' => false,
                    'error' => "No access to division '{$connection->division}'",
                ];
            }

            return [
                'accessible' => false,
                'error' => 'Division access error: '.$e->getMessage(),
            ];

        } catch (\Exception $e) {
            return [
                'accessible' => false,
                'error' => 'Failed to verify division access: '.$e->getMessage(),
            ];
        }
    }
}
