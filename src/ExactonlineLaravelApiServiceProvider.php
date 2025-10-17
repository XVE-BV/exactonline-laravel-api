<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi;

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
        // Register any package services here
    }

    public function packageRegistered(): void
    {
        // Register any bindings or singletons here
    }
}
