<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Mcp\Support;

use Illuminate\Support\Collection;

/**
 * Scrubs OAuth credentials and webhook secrets from model output.
 *
 * Applied to toArray() output from local DB models before returning tool results.
 * Never calls getDecrypted*() methods; works on the serialized array form only
 * so encrypted ciphertext is stripped rather than decrypted and leaked.
 */
class SecretScrubber
{
    /**
     * Fields that are always removed, regardless of context.
     *
     * Covers ExactConnection OAuth secrets and ExactWebhook HMAC secret.
     *
     * @var array<string>
     */
    private const EXACT_FIELDS = [
        'access_token',
        'refresh_token',
        'client_secret',
        'client_id',
        'tenant_id',
        'webhook_secret',
    ];

    /**
     * Substring patterns that trigger removal on nested / metadata keys.
     *
     * Any key whose lowercase form contains one of these strings will be
     * removed, unless the key also matches SAFE_SUFFIXES.
     *
     * @var array<string>
     */
    private const SENSITIVE_PATTERNS = [
        'token',
        'secret',
        'password',
        'authorization',
        'api_key',
        'bearer',
    ];

    /**
     * Key suffixes that are safe to keep even when they match a sensitive pattern.
     *
     * e.g. "token_expires_at" contains "token" but is safe business metadata.
     *
     * @var array<string>
     */
    private const SAFE_SUFFIXES = [
        '_expires_at',
        '_needs_refresh',
        '_expiring_soon',
        '_expiry',
        '_status',
        '_health',
        '_count',
        '_at',
    ];

    /**
     * Scrub all secret fields from a data array, including nested arrays.
     *
     * Apply to local-model output (connections, webhooks, mappings, etc.) where
     * metadata can contain arbitrary credential keys.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function scrubFull(array $data): array
    {
        return $this->scrubRecursive($data, full: true);
    }

    /**
     * Scrub only the known exact fields from a data array.
     *
     * Apply to Exact API business-entity responses where the denylisted patterns
     * would otherwise remove legitimate business fields (e.g. a field called
     * "PaymentToken" in an invoice response).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function scrubKnownFields(array $data): array
    {
        return $this->scrubRecursive($data, full: false);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function scrubRecursive(array $data, bool $full): array
    {
        $scrubbed = [];

        foreach ($data as $key => $value) {
            $keyLower = strtolower((string) $key);

            // Always remove exact-named credential fields.
            if (in_array($keyLower, self::EXACT_FIELDS, true)) {
                continue;
            }

            // In full-scrub mode, also remove keys matching sensitive patterns
            // unless the key ends with a known-safe suffix.
            if ($full && $this->isSensitiveKey($keyLower)) {
                continue;
            }

            // Recurse into nested arrays (handles metadata[], eager-loaded relations, etc.)
            if (is_array($value)) {
                $scrubbed[$key] = $this->scrubRecursive($value, $full);
            } elseif ($value instanceof Collection) {
                $scrubbed[$key] = $value->map(fn ($item) => is_array($item)
                    ? $this->scrubRecursive($item, $full)
                    : $item
                );
            } else {
                $scrubbed[$key] = $value;
            }
        }

        return $scrubbed;
    }

    private function isSensitiveKey(string $keyLower): bool
    {
        foreach (self::SAFE_SUFFIXES as $suffix) {
            if (str_ends_with($keyLower, $suffix)) {
                return false;
            }
        }

        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            if (str_contains($keyLower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
