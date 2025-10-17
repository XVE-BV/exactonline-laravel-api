<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\OAuth;

use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Events\TokensRevoked;
use Skylence\ExactonlineLaravelApi\Models\ExactConnection;

class RevokeTokensAction
{
    /**
     * Revoke OAuth tokens and mark connection as inactive
     *
     * This action revokes the tokens both locally (in database) and optionally
     * with Exact Online's revocation endpoint if available.
     *
     * @param  bool  $notifyExactOnline  Whether to notify Exact Online about revocation
     */
    public function execute(
        ExactConnection $connection,
        bool $notifyExactOnline = true
    ): void {
        // Attempt to revoke tokens with Exact Online if requested
        if ($notifyExactOnline && $connection->access_token) {
            $this->revokeWithExactOnline($connection);
        }

        // Clear tokens from database
        $this->clearTokensFromDatabase($connection);

        // Dispatch event
        event(new TokensRevoked($connection));

        Log::info('Tokens revoked successfully', [
            'connection_id' => $connection->id,
            'notified_exact' => $notifyExactOnline,
        ]);
    }

    /**
     * Attempt to revoke tokens with Exact Online
     *
     * Note: Exact Online may not support token revocation endpoint.
     * This is a best-effort attempt and failures are logged but not thrown.
     */
    protected function revokeWithExactOnline(ExactConnection $connection): void
    {
        try {
            $accessToken = $connection->getDecryptedAccessToken();
            if (! $accessToken) {
                return;
            }

            // Note: picqer/exact-php-client doesn't have a built-in revoke method
            // This is where we would call Exact Online's revocation endpoint if available
            // For now, we just log the attempt

            Log::info('Token revocation with Exact Online not implemented', [
                'connection_id' => $connection->id,
                'reason' => 'Exact Online API does not provide a token revocation endpoint',
            ]);

            // In the future, if Exact Online adds a revocation endpoint:
            // $this->callRevocationEndpoint($connection, $accessToken);

        } catch (\Exception $e) {
            // Log but don't throw - revocation with provider is best-effort
            Log::warning('Failed to revoke tokens with Exact Online', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear tokens from the database
     */
    protected function clearTokensFromDatabase(ExactConnection $connection): void
    {
        $connection->update([
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
            'refresh_token_expires_at' => null,
            'is_active' => false,
        ]);
    }
}
