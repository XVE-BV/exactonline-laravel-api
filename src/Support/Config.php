<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Support;

use XVE\ExactonlineLaravelApi\Exceptions\InvalidActionClass;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;
use XVE\ExactonlineLaravelApi\Models\ExactMapping;
use XVE\ExactonlineLaravelApi\Models\ExactRateLimit;
use XVE\ExactonlineLaravelApi\Models\ExactWebhook;

class Config
{
    /**
     * Get action class from config with type validation
     *
     * @template T
     *
     * @param  class-string<T>  $actionBaseClass
     * @return class-string<T>
     *
     * @throws InvalidActionClass
     */
    public static function getActionClass(string $actionName, string $actionBaseClass): string
    {
        $actionClass = config("exactonline-laravel-api.actions.{$actionName}");

        self::ensureValidActionClass($actionName, $actionBaseClass, $actionClass);

        return $actionClass;
    }

    /**
     * Get fresh action instance with type validation
     *
     * @template T
     *
     * @param  class-string<T>  $actionBaseClass
     * @return T
     *
     * @throws InvalidActionClass
     */
    public static function getAction(string $actionName, string $actionBaseClass)
    {
        $actionClass = self::getActionClass($actionName, $actionBaseClass);

        return app($actionClass);
    }

    /**
     * Validate action class is correct type
     */
    protected static function ensureValidActionClass(
        string $actionName,
        string $actionBaseClass,
        ?string $actionClass
    ): void {
        if ($actionClass === null) {
            throw InvalidActionClass::notConfigured($actionName);
        }

        if (! class_exists($actionClass)) {
            throw InvalidActionClass::doesNotExist($actionName, $actionClass);
        }

        if (! is_a($actionClass, $actionBaseClass, true)) {
            throw InvalidActionClass::invalidType($actionName, $actionBaseClass, $actionClass);
        }
    }

    /**
     * Helper methods for common config values
     */
    public static function getRelyingPartyName(): string
    {
        return config('exactonline-laravel-api.relying_party.name', config('app.name'));
    }

    public static function getConnectionModel(): string
    {
        return config('exactonline-laravel-api.models.connection', ExactConnection::class);
    }

    public static function getWebhookModel(): string
    {
        return config('exactonline-laravel-api.models.webhook', ExactWebhook::class);
    }

    public static function getClientId(): string
    {
        return config('exactonline-laravel-api.oauth.client_id', '');
    }

    public static function getClientSecret(): string
    {
        return config('exactonline-laravel-api.oauth.client_secret', '');
    }

    public static function getRedirectUrl(): string
    {
        return config('exactonline-laravel-api.oauth.redirect_url', '/exact/oauth/callback');
    }

    public static function getRefreshTokenBufferDays(): int
    {
        return (int) config('exactonline-laravel-api.oauth.refresh_token_buffer_days', 7);
    }

    public static function shouldWaitOnMinutelyLimit(): bool
    {
        return config('exactonline-laravel-api.rate_limiting.wait_on_minutely_limit', true);
    }

    public static function shouldThrowOnDailyLimit(): bool
    {
        return config('exactonline-laravel-api.rate_limiting.throw_on_daily_limit', true);
    }

    public static function getMappingModel(): string
    {
        return config('exactonline-laravel-api.models.mapping', ExactMapping::class);
    }

    public static function getMappingEnvironment(): string
    {
        return config('exactonline-laravel-api.mapping.environment', config('app.env', 'production'));
    }

    public static function getRateLimitModel(): string
    {
        return config('exactonline-laravel-api.models.rate_limit', ExactRateLimit::class);
    }

    /**
     * Guzzle middleware for every picqer connection, resolved from the
     * container when given as a class name.
     *
     * @return list<callable>
     */
    public static function getHttpMiddleware(): array
    {
        $middleware = [];

        foreach ((array) config('exactonline-laravel-api.connection.http_middleware', []) as $entry) {
            $resolved = is_string($entry) && class_exists($entry) ? app($entry) : $entry;

            if (! is_callable($resolved)) {
                throw new \InvalidArgumentException(sprintf(
                    'exactonline-laravel-api.connection.http_middleware entries must be Guzzle middleware callables or invokable classes, got %s.',
                    get_debug_type($resolved),
                ));
            }

            $middleware[] = $resolved;
        }

        return $middleware;
    }
}
