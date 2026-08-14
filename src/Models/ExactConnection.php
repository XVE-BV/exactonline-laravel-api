<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Models;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Connection;
use XVE\ExactonlineLaravelApi\Actions\OAuth\StoreTokensAction;
use XVE\ExactonlineLaravelApi\Database\Factories\ExactConnectionFactory;
use XVE\ExactonlineLaravelApi\Support\Config;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $tenant_id
 * @property string|null $division
 * @property int|null $division_id
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property int|null $token_expires_at
 * @property Carbon|null $last_token_refresh_at
 * @property int|null $refresh_token_expires_at
 * @property string|null $client_id
 * @property string|null $client_secret
 * @property string|null $redirect_url
 * @property string $base_url
 * @property bool $is_active
 * @property Carbon|null $last_used_at
 * @property string|null $name
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ExactWebhook> $webhooks
 * @property-read ExactRateLimit|null $rateLimit
 * @property-read Collection<int, ExactMapping> $mappings
 * @property-read Collection<int, ExactDivision> $divisions
 * @property-read ExactDivision|null $activeDivision
 */
class ExactConnection extends Model
{
    /** @use HasFactory<ExactConnectionFactory> */
    use HasFactory;

    /**
     * Lock held while picqer acquires a token on its own initiative.
     *
     * Transient and request-scoped: it is taken in
     * acquirePicqerRefreshLock() and released from picqer's finally block, so
     * it is never held across a queue boundary. Declared here (rather than
     * left undeclared) so Eloquent treats it as a plain property instead of
     * routing it through the attribute bag.
     *
     * @var Lock|null
     */
    protected $picqerRefreshLock = null;

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
        'division_id',
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
        'division_id' => 'integer',
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
     * Get all mappings for this connection.
     *
     * @return HasMany<ExactMapping, $this>
     */
    public function mappings(): HasMany
    {
        return $this->hasMany(ExactMapping::class, 'connection_id');
    }

    /**
     * Get all divisions for this connection.
     *
     * @return HasMany<ExactDivision, $this>
     */
    public function divisions(): HasMany
    {
        return $this->hasMany(ExactDivision::class, 'connection_id');
    }

    /**
     * Get the synced division row for the active division code.
     *
     * @return BelongsTo<ExactDivision, $this>
     */
    public function activeDivision(): BelongsTo
    {
        return $this->belongsTo(ExactDivision::class, 'division_id');
    }

    /**
     * Resolve the synced division row for the current Exact division code.
     */
    public function resolveDivisionId(): void
    {
        $divisionId = null;

        if ($this->division !== null) {
            $divisionId = ExactDivision::query()
                ->where('connection_id', $this->id)
                ->where('code', $this->division)
                ->value('id');
        }

        $divisionId = $divisionId === null ? null : (int) $divisionId;

        if ($this->division_id === $divisionId) {
            $this->forceFill(['division_id' => $divisionId]);

            return;
        }

        $this->forceFill(['division_id' => $divisionId]);

        if ($this->exists) {
            $this->saveQuietly();
        }
    }

    /**
     * Scope a query to only include active connections.
     *
     * @param  Builder<ExactConnection>  $query
     * @return Builder<ExactConnection>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include connections with expired tokens.
     *
     * @param  Builder<ExactConnection>  $query
     * @return Builder<ExactConnection>
     */
    public function scopeExpired($query)
    {
        return $query->where('token_expires_at', '<', now()->getTimestamp());
    }

    /**
     * Scope a query to only include connections that need token refresh.
     * Proactive refresh at 9 minutes (540 seconds before expiry).
     *
     * @param  Builder<ExactConnection>  $query
     * @return Builder<ExactConnection>
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

        // Exact rotates its single-use refresh token on EVERY refresh: the old
        // one is invalidated the moment a new one is issued. picqer performs
        // that refresh itself inside Connection::acquireAccessToken() and
        // surfaces the new pair only through this callback. Without it the
        // rotated token lives in memory for the rest of the request and is then
        // discarded, while Exact has already killed the copy we have stored —
        // so every later refresh fails and the connection is silently dead
        // until the stored token reaches its ~30-day idle expiry.
        //
        // One known limit: picqer holds ONE callback slot and
        // setTokenUpdateCallback overwrites it. A consumer that registers its
        // own on the returned connection silently disables this persistence
        // and reopens the outage above; such a consumer must chain the
        // persist itself.
        $connection->setTokenUpdateCallback(function (Connection $picqer): void {
            $this->persistRotatedTokens($picqer);
        });

        // Serialise picqer-initiated refreshes. Without this, two workers
        // hitting an expired token at once both POST the same single-use
        // refresh token: one wins, the other gets invalid_grant and its call
        // fails. The lock makes the loser wait, and the refresh callback then
        // hands it the winner's freshly stored token so it skips its own
        // request entirely.
        //
        // The key is deliberately NOT RefreshAccessTokenAction's
        // "exact-token-refresh:{id}". That action calls getPicqerConnection()
        // internally (RefreshAccessTokenAction::performTokenRefresh), so
        // reusing its key would have the action deadlock against its own lock
        // the moment picqer asked for a token. For the same reason the refresh
        // callback below only READS the stored row — routing it back through
        // the action would recurse action -> picqer -> action.
        $lockKey = 'exact-picqer-token-refresh:'.$this->getKey();

        $connection->setAcquireAccessTokenLockCallback(function () use ($lockKey): void {
            $this->acquirePicqerRefreshLock($lockKey);
        });

        $connection->setAcquireAccessTokenUnlockCallback(function (): void {
            $this->releasePicqerRefreshLock();
        });

        $connection->setRefreshAccessTokenCallback(function (Connection $picqer): void {
            $this->adoptStoredTokensIfFresh($picqer);
        });

        return $connection;
    }

    /**
     * Persist a token pair picqer refreshed on its own initiative.
     *
     * Best-effort by design: this fires from inside an in-flight Exact request,
     * and failing to record the rotation must not turn a successful API call
     * into an exception. A failure is loud in the log because it means the
     * chain is about to break.
     */
    protected function persistRotatedTokens(Connection $picqer): void
    {
        $accessToken = $picqer->getAccessToken();
        $refreshToken = $picqer->getRefreshToken();

        if (empty($accessToken) || empty($refreshToken)) {
            return;
        }

        try {
            Config::getAction('store_tokens', StoreTokensAction::class)->execute($this, [
                'access_token' => (string) $accessToken,
                'refresh_token' => (string) $refreshToken,
                'expires_at' => $picqer->getTokenExpires(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to persist refresh token rotated by picqer — the Exact token chain is now broken for this connection', [
                'connection_id' => $this->id,
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Adopt the stored token pair when another worker already refreshed.
     *
     * picqer calls this at the top of acquireAccessToken() and, if the
     * connection is no longer expired afterwards, returns without making its
     * own token request. So when we lost the race for the lock above, this is
     * what stops us from POSTing a refresh token the winner already consumed.
     *
     * Deliberately a plain read of the stored row: routing it through
     * RefreshAccessTokenAction would recurse, because that action builds a
     * picqer connection of its own.
     */
    protected function adoptStoredTokensIfFresh(Connection $picqer): void
    {
        try {
            $stored = $this->fresh();
        } catch (\Throwable $e) {
            return;
        }

        if ($stored === null || empty($stored->token_expires_at)) {
            return;
        }

        // picqer treats a token as expired 10s before its stamp, so require
        // more than that much life left or it will refresh anyway. A stale row
        // is left alone: adopting it would only downgrade what picqer holds.
        if ($stored->token_expires_at <= (now()->getTimestamp() + 10)) {
            return;
        }

        $accessToken = $stored->getDecryptedAccessToken();
        if (empty($accessToken)) {
            return;
        }

        $picqer->setAccessToken($accessToken);
        $picqer->setTokenExpires($stored->token_expires_at);

        $refreshToken = $stored->getDecryptedRefreshToken();
        if (! empty($refreshToken)) {
            $picqer->setRefreshToken($refreshToken);
        }
    }

    /**
     * Hold a lock for the duration of picqer's own token acquisition.
     *
     * Blocking rather than failing is the point: the waiter goes on to adopt
     * the winner's token instead of burning the rotated one. Lock trouble is
     * never allowed to break the API call — the worst case is the
     * unsynchronised behaviour we had before.
     */
    protected function acquirePicqerRefreshLock(string $lockKey): void
    {
        $this->picqerRefreshLock = null;

        try {
            $lock = Cache::lock($lockKey, 30);
            $lock->block(15);
            $this->picqerRefreshLock = $lock;
        } catch (\Throwable $e) {
            Log::warning('Could not serialise picqer token refresh; proceeding unsynchronised', [
                'connection_id' => $this->id,
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Release the lock taken by acquirePicqerRefreshLock(). picqer calls this
     * from a finally block, so it also runs when the refresh threw.
     */
    protected function releasePicqerRefreshLock(): void
    {
        if ($this->picqerRefreshLock === null) {
            return;
        }

        try {
            $this->picqerRefreshLock->release();
        } catch (\Throwable $e) {
            // A lock we cannot release will lapse on its own 30s TTL.
        }

        $this->picqerRefreshLock = null;
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

    protected static function newFactory(): ExactConnectionFactory
    {
        return ExactConnectionFactory::new();
    }
}
