<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi;

use Illuminate\Routing\Router;
use Skylence\ExactonlineLaravelApi\Http\Middleware\CheckExactRateLimit;
use Skylence\ExactonlineLaravelApi\Http\Middleware\EnsureValidExactConnection;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ExactonlineLaravelApiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('exactonline-laravel-api')
            ->hasConfigFile('exactonline-laravel-api')
            ->hasMigrations([
                'create_exact_connections_table',
                'create_exact_webhooks_table',
                'create_exact_rate_limits_table',
            ])
            ->hasRoute('web');
    }

    public function packageBooted(): void
    {
        // Register middleware aliases
        $this->registerMiddleware();
    }

    public function packageRegistered(): void
    {
        // Register any bindings or singletons here
    }

    /**
     * Register the package middleware
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        // Register middleware aliases for easy use in routes
        $router->aliasMiddleware('exact.connection', EnsureValidExactConnection::class);
        $router->aliasMiddleware('exact.rate_limit', CheckExactRateLimit::class);

        // Register middleware groups
        $router->middlewareGroup('exact', [
            'exact.connection',
            'exact.rate_limit',
        ]);
    }
}
