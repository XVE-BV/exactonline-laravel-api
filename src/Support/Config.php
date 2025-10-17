<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Support;

use Skylence\ExactonlineLaravelApi\Exceptions\InvalidActionClass;

class Config
{
    /**
     * Get action class from config with type validation
     *
     * @template T
     * @param string $actionName
     * @param class-string<T> $actionBaseClass
     * @return class-string<T>
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
     * @param string $actionName
     * @param class-string<T> $actionBaseClass
     * @return T
     * @throws InvalidActionClass
     */
    public static function getAction(string $actionName, string $actionBaseClass)
    {
        $actionClass = self::getActionClass($actionName, $actionBaseClass);

        return new $actionClass();
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
        return config('exactonline-laravel-api.models.connection', \Skylence\ExactonlineLaravelApi\Models\ExactConnection::class);
    }

    public static function getWebhookModel(): string
    {
        return config('exactonline-laravel-api.models.webhook', \Skylence\ExactonlineLaravelApi\Models\ExactWebhook::class);
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

    public static function shouldWaitOnMinutelyLimit(): bool
    {
        return config('exactonline-laravel-api.rate_limiting.wait_on_minutely_limit', true);
    }

    public static function shouldThrowOnDailyLimit(): bool
    {
        return config('exactonline-laravel-api.rate_limiting.throw_on_daily_limit', true);
    }
}
