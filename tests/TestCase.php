<?php

namespace XVE\ExactonlineLaravelApi\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use XVE\ExactonlineLaravelApi\ExactonlineLaravelApiServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'XVE\\ExactonlineLaravelApi\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // Load package migrations for tests
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            ExactonlineLaravelApiServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        // Database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Application key required for encryption
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        // Use array session driver for feature tests
        $app['config']->set('session.driver', 'array');
    }
}
