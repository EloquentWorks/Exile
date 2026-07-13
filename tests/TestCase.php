<?php

namespace Tests;

use EloquentWorks\Exile\ExileServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Tests\Fixtures\TestUser;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [ExileServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
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

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function user(string $name = 'User'): TestUser
    {
        return TestUser::query()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
        ]);
    }
}
