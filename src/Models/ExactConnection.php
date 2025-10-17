<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;
use Picqer\Financials\Exact\Connection;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $tenant_id
 * @property string|null $division
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property int|null $token_expires_at
 * @property \Illuminate\Support\Carbon|null $last_token_refresh_at
 * @property int|null $refresh_token_expires_at
 * @property string|null $client_id
 * @property string|null $client_secret
 * @property string|null $redirect_url
 * @property string $base_url
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property string|null $name
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExactWebhook> $webhooks
 * @property-read ExactRateLimit|null $rateLimit
 */
class ExactConnection extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'exact_connections';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'tenant_id',
        'division',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'last_token_refresh_at',
        'refresh_token_expires_at',
        'client_id',
        'client_secret',
        'redirect_url',
        'base_url',
        'is_active',
        'last_used_at',
        'name',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'last_token_refresh_at' => 'datetime',
        'last_used_at' => 'datetime',
        'token_expires_at' => 'integer',
        'refresh_token_expires_at' => 'integer',
    ];

    /**
     * The attributes that should be encrypted.
     *
     * @var array<string>
     */
    protected $encrypted = [
        'access_token',
        'refresh_token',
        'client_secret',
    ];

    /**
     * Get the webhooks for this connection.
     *
     * @return HasMany<ExactWebhook, $this>
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(ExactWebhook::class, 'connection_id');
    }

    /**
     * Get the rate limit record for this connection.
     *
     * @return HasOne<ExactRateLimit, $this>
     */
    public function rateLimit(): HasOne
    {
        return $this->hasOne(ExactRateLimit::class, 'connection_id');
    }

    /**
     * Scope a query to only include active connections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ExactConnection>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ExactConnection>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include connections with expired tokens.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ExactConnection>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ExactConnection>
     */
    public function scopeExpired($query)
    {
        return $query->where('token_expires_at', '<', now()->timestamp);
    }

    /**
     * Scope a query to only include connections that need token refresh.
     * Proactive refresh at 9 minutes (540 seconds before expiry).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ExactConnection>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ExactConnection>
     */
    public function scopeNeedsRefresh($query)
    {
        $thresholdTimestamp = now()->addSeconds(540)->timestamp;

        return $query->where('is_active', true)
            ->where(function ($q) use ($thresholdTimestamp) {
                $q->whereNull('token_expires_at')
                    ->orWhere('token_expires_at', '<', $thresholdTimestamp);
            });
    }

    /**
     * Get a picqer Connection instance for this Exact connection.
     */
    public function getPicqerConnection(): Connection
    {
        $connection = new Connection;

        $connection->setBaseUrl($this->base_url);

        if ($this->division) {
            $connection->setDivision($this->division);
        }

        if ($this->access_token) {
            $connection->setAccessToken($this->getDecryptedAccessToken());
        }

        if ($this->refresh_token) {
            $connection->setRefreshToken($this->getDecryptedRefreshToken());
        }

        if ($this->token_expires_at) {
            $connection->setTokenExpires($this->token_expires_at);
        }

        // Set OAuth client credentials
        $connection->setExactClientId($this->client_id);
        $connection->setExactClientSecret($this->getDecryptedClientSecret());
        $connection->setRedirectUrl($this->redirect_url);

        return $connection;
    }

    /**
     * Check if the access token needs refresh.
     * Proactive refresh at 9 minutes (540 seconds before expiry).
     */
    public function tokenNeedsRefresh(): bool
    {
        if (! $this->token_expires_at) {
            return true;
        }

        // Refresh proactively at 9 minutes (540 seconds before expiry)
        return $this->token_expires_at < (now()->getTimestamp() + 540);
    }

    /**
     * Check if the refresh token is expiring soon.
     */
    public function refreshTokenExpiringSoon(int $daysThreshold = 7): bool
    {
        if (! $this->refresh_token_expires_at) {
            return false;
        }

        $thresholdTimestamp = now()->addDays($daysThreshold)->timestamp;

        return $this->refresh_token_expires_at < $thresholdTimestamp;
    }

    /**
     * Mark this connection as used.
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get decrypted access token.
     */
    public function getDecryptedAccessToken(): ?string
    {
        if (! $this->access_token) {
            return null;
        }

        try {
            return Crypt::decryptString($this->access_token);
        } catch (\Exception $e) {
            // If decryption fails, assume it's not encrypted (for backwards compatibility)
            return $this->access_token;
        }
    }

    /**
     * Get decrypted refresh token.
     */
    public function getDecryptedRefreshToken(): ?string
    {
        if (! $this->refresh_token) {
            return null;
        }

        try {
            return Crypt::decryptString($this->refresh_token);
        } catch (\Exception $e) {
            // If decryption fails, assume it's not encrypted (for backwards compatibility)
            return $this->refresh_token;
        }
    }

    /**
     * Get decrypted client secret.
     */
    public function getDecryptedClientSecret(): ?string
    {
        if (! $this->client_secret) {
            return null;
        }

        try {
            return Crypt::decryptString($this->client_secret);
        } catch (\Exception $e) {
            // If decryption fails, assume it's not encrypted (for backwards compatibility)
            return $this->client_secret;
        }
    }

    /**
     * Set and encrypt the access token.
     */
    public function setAccessTokenAttribute(?string $token): void
    {
        $this->attributes['access_token'] = $token ? Crypt::encryptString($token) : null;
    }

    /**
     * Set and encrypt the refresh token.
     */
    public function setRefreshTokenAttribute(?string $token): void
    {
        $this->attributes['refresh_token'] = $token ? Crypt::encryptString($token) : null;
    }

    /**
     * Set and encrypt the client secret.
     */
    public function setClientSecretAttribute(?string $secret): void
    {
        $this->attributes['client_secret'] = $secret ? Crypt::encryptString($secret) : null;
    }
}
