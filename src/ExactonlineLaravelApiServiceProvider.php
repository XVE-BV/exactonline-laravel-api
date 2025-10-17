<?php

namespace Skylence\ExactonlineLaravelApi;

use Skylence\ExactonlineLaravelApi\Commands\ExactonlineLaravelApiCommand;
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
            ->hasViews()
            ->hasMigration('create_exactonline_laravel_api_table')
            ->hasCommand(ExactonlineLaravelApiCommand::class);
    }
}
