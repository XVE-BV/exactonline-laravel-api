<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Actions\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Skylence\ExactonlineLaravelApi\Models\ExactWebhook;

class ValidateWebhookSignatureAction
{
    /**
     * Validate webhook signature using HMAC-SHA256
     *
     * This follows the same pattern as picqer's Webhook\Authenticatable trait
     *
     * @param  Request  $request  The incoming webhook request
     * @param  ExactWebhook|null  $webhook  The webhook record (optional, will be looked up if not provided)
     * @return array{
     *     valid: bool,
     *     webhook: ExactWebhook|null,
     *     topic: string|null,
     *     error: string|null
     * }
     */
    public function execute(Request $request, ?ExactWebhook $webhook = null): array
    {
        $result = [
            'valid' => false,
            'webhook' => null,
            'topic' => null,
            'error' => null,
        ];

        try {
            // Extract topic from request
            $topic = $this->extractTopic($request);
            $result['topic'] = $topic;

            if (empty($topic)) {
                $result['error'] = 'No webhook topic found in request';

                return $result;
            }

            // Find webhook if not provided
            if ($webhook === null) {
                $webhook = $this->findWebhook($request, $topic);
            }

            if ($webhook === null) {
                $result['error'] = "No registered webhook found for topic: {$topic}";

                return $result;
            }

            $result['webhook'] = $webhook;

            // Validate signature
            $signature = $this->extractSignature($request);

            if (empty($signature)) {
                $result['error'] = 'No signature found in request headers';

                return $result;
            }

            // Calculate expected signature
            $payload = $request->getContent();
            $expectedSignature = $this->calculateSignature($payload, $webhook);

            // Compare signatures (timing-safe)
            if (! hash_equals($expectedSignature, $signature)) {
                $result['error'] = 'Invalid webhook signature';

                Log::warning('Webhook signature validation failed', [
                    'webhook_id' => $webhook->id,
                    'topic' => $topic,
                    'received_signature' => substr($signature, 0, 10).'...',
                    'expected_signature' => substr($expectedSignature, 0, 10).'...',
                ]);

                return $result;
            }

            // Additional validation: Check timestamp to prevent replay attacks
            if (! $this->validateTimestamp($request)) {
                $result['error'] = 'Webhook timestamp is too old (possible replay attack)';

                return $result;
            }

            $result['valid'] = true;

            Log::info('Webhook signature validated successfully', [
                'webhook_id' => $webhook->id,
                'topic' => $topic,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Error validating webhook signature', [
                'error' => $e->getMessage(),
                'topic' => $result['topic'],
            ]);

            $result['error'] = 'Validation error: '.$e->getMessage();

            return $result;
        }
    }

    /**
     * Extract webhook topic from request
     */
    protected function extractTopic(Request $request): ?string
    {
        // Try to get topic from header first
        $topic = $request->header('X-ExactOnline-Topic');

        if (! empty($topic)) {
            return $topic;
        }

        // Try to extract from payload
        $payload = $request->json();

        if ($payload && isset($payload['Topic'])) {
            return $payload['Topic'];
        }

        // Try alternative field names
        if ($payload && isset($payload['topic'])) {
            return $payload['topic'];
        }

        return null;
    }

    /**
     * Extract signature from request headers
     */
    protected function extractSignature(Request $request): ?string
    {
        // Exact Online typically sends signature in this header
        $signature = $request->header('X-ExactOnline-Signature');

        if (! empty($signature)) {
            return $signature;
        }

        // Try alternative header names
        $signature = $request->header('X-Webhook-Signature');

        if (! empty($signature)) {
            return $signature;
        }

        // Try standard authorization header
        $authorization = $request->header('Authorization');
        if (! empty($authorization) && str_starts_with($authorization, 'Signature ')) {
            return substr($authorization, 10);
        }

        return null;
    }

    /**
     * Find webhook by request and topic
     */
    protected function findWebhook(Request $request, string $topic): ?ExactWebhook
    {
        // Try to find by callback URL and topic
        $callbackUrl = $request->fullUrl();

        $webhook = ExactWebhook::where('topic', $topic)
            ->where('callback_url', $callbackUrl)
            ->where('is_active', true)
            ->first();

        if ($webhook !== null) {
            return $webhook;
        }

        // Try to find just by topic (if URL doesn't match exactly)
        $webhook = ExactWebhook::where('topic', $topic)
            ->where('is_active', true)
            ->first();

        if ($webhook !== null) {
            // Log URL mismatch for debugging
            Log::debug('Webhook found but callback URL mismatch', [
                'topic' => $topic,
                'expected_url' => $webhook->callback_url,
                'received_url' => $callbackUrl,
            ]);
        }

        return $webhook;
    }

    /**
     * Calculate HMAC-SHA256 signature
     */
    protected function calculateSignature(string $payload, ExactWebhook $webhook): string
    {
        $secret = decrypt($webhook->webhook_secret);

        // Calculate HMAC-SHA256
        $signature = hash_hmac('sha256', $payload, $secret);

        // Exact Online may use base64 encoding
        // Check webhook metadata for encoding type
        $metadata = $webhook->metadata ?? [];

        if (isset($metadata['signature_encoding']) && $metadata['signature_encoding'] === 'base64') {
            return base64_encode(hex2bin($signature));
        }

        return $signature;
    }

    /**
     * Validate timestamp to prevent replay attacks
     */
    protected function validateTimestamp(Request $request): bool
    {
        // Get timestamp from header or payload
        $timestamp = $request->header('X-ExactOnline-Timestamp');

        if (empty($timestamp)) {
            // Try to get from payload
            $payload = $request->json();
            $timestamp = $payload['timestamp'] ?? $payload['Timestamp'] ?? null;
        }

        if (empty($timestamp)) {
            // No timestamp to validate, consider it valid
            return true;
        }

        // Parse timestamp (could be Unix timestamp or ISO 8601)
        if (is_numeric($timestamp)) {
            $webhookTime = (int) $timestamp;
        } else {
            $webhookTime = strtotime($timestamp);
        }

        if ($webhookTime === false) {
            return false;
        }

        // Check if timestamp is within acceptable window (5 minutes)
        $currentTime = now()->timestamp;
        $timeDifference = abs($currentTime - $webhookTime);

        if ($timeDifference > 300) { // 5 minutes
            Log::warning('Webhook timestamp outside acceptable window', [
                'webhook_time' => $webhookTime,
                'current_time' => $currentTime,
                'difference' => $timeDifference,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Validate webhook signature (static helper method)
     */
    public static function isValidSignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
