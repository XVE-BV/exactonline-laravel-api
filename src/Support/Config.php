<?php

declare(strict_types=1);

namespace XVE\Exactonline\Support;

use XVE\Exactonline\Exceptions\InvalidActionClass;
use XVE\Exactonline\Models\ExactConnection;
use XVE\Exactonline\Models\ExactMapping;
use XVE\Exactonline\Models\ExactRateLimit;
use XVE\Exactonline\Models\ExactWebhook;

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
        $actionClass = config("exactonline.actions.{$actionName}");

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
        return config('exactonline.relying_party.name', config('app.name'));
    }

    public static function getConnectionModel(): string
    {
        return config('exactonline.models.connection', ExactConnection::class);
    }

    public static function getWebhookModel(): string
    {
        return config('exactonline.models.webhook', ExactWebhook::class);
    }

    public static function getClientId(): string
    {
        return config('exactonline.oauth.client_id', '');
    }

    public static function getClientSecret(): string
    {
        return config('exactonline.oauth.client_secret', '');
    }

    public static function getRedirectUrl(): string
    {
        return config('exactonline.oauth.redirect_url', '/exact/oauth/callback');
    }

    public static function shouldWaitOnMinutelyLimit(): bool
    {
        return config('exactonline.rate_limiting.wait_on_minutely_limit', true);
    }

    public static function shouldThrowOnDailyLimit(): bool
    {
        return config('exactonline.rate_limiting.throw_on_daily_limit', true);
    }

    public static function getMappingModel(): string
    {
        return config('exactonline.models.mapping', ExactMapping::class);
    }

    public static function getMappingEnvironment(): string
    {
        return config('exactonline.mapping.environment', config('app.env', 'production'));
    }

    public static function getRateLimitModel(): string
    {
        return config('exactonline.models.rate_limit', ExactRateLimit::class);
    }
}
