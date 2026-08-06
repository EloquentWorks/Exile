<?php

namespace Tests;

use EloquentWorks\Exile\ExileServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Tests\Fixtures\TestUser;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * Get the package providers for the application.
     *
     * @param  Application  $app
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [ExileServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Set the Exile package configuration for testing
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('x', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('exile.security.hash_key', 'testing-exile-key');
        $app['config']->set('exile.notifications.enabled', false);
        $app['config']->set('exile.retention.prune_enabled', false);
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        // Load the migrations from the package's database/migrations directory
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Create a test user with the given name.
     *
     * @param  string  $name  The name of the test user.
     * @return TestUser The created test user instance.
     */
    protected function user(string $name = 'User'): TestUser
    {
        // Create a new test user with the specified name and a generated email address
        return TestUser::query()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
        ]);
    }
}
