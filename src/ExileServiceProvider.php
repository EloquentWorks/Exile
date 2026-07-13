<?php

namespace EloquentWorks\Exile;

use EloquentWorks\Exile\Commands\ExpireEnforcementsCommand;
use EloquentWorks\Exile\Commands\InstallExileCommand;
use EloquentWorks\Exile\Commands\PruneExileCommand;
use EloquentWorks\Exile\Middleware\EnsureActionAllowed;
use EloquentWorks\Exile\Middleware\EnsureNotBanned;
use EloquentWorks\Exile\Middleware\MarkShadowBanned;
use EloquentWorks\Exile\Services\ExileManager;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Exile package.
 */
final class ExileServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void Returns nothing.
     */
    public function register(): void
    {
        // Merge the package's configuration file with the application's configuration
        $this->mergeConfigFrom(__DIR__.'/../config/exile.php', 'exile');

        // Register the ExileManager service as a singleton in the application container
        $this->app->singleton(ExileManager::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @param  Router  $router  The router instance for registering middleware.
     * @return void Returns nothing.
     */
    public function boot(Router $router): void
    {
        // Load the package's migrations from the specified directory
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register middleware aliases for handling bans, restrictions, and shadow bans
        $router->aliasMiddleware((string) config('exile.middleware.ban_alias', 'exile'), EnsureNotBanned::class);
        $router->aliasMiddleware((string) config('exile.middleware.restriction_alias', 'exile.allowed'), EnsureActionAllowed::class);
        $router->aliasMiddleware((string) config('exile.middleware.shadow_alias', 'exile.shadow'), MarkShadowBanned::class);

        // Publish configuration and migrations, and register commands if running in console
        if ($this->app->runningInConsole()) {
            // Publish configuration file for the Exile package
            $this->publishes([
                __DIR__.'/../config/exile.php' => config_path('exile.php'),
            ], 'exile-config');

            // Publish migration files for the Exile package
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'exile-migrations');

            // Register console commands for installation, expiration, and pruning
            $this->commands([
                InstallExileCommand::class,
                ExpireEnforcementsCommand::class,
                PruneExileCommand::class,
            ]);
        }

        // Schedule the expiration and pruning commands based on configuration settings
        if (config('exile.schedule.enabled', true)) {
            $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
                // Schedule the command to process expired bans and restrictions
                $this->applyFrequency(
                    $schedule->command('exile:expire'),
                    (string) config('exile.schedule.expire_frequency', 'hourly')
                );

                // Schedule the command to prune old records if pruning is enabled in the configuration
                if (config('exile.retention.prune_enabled', false)) {
                    $this->applyFrequency(
                        $schedule->command('exile:prune'),
                        (string) config('exile.schedule.prune_frequency', 'daily')
                    );
                }
            });
        }
    }

    /**
     * Apply the specified frequency to the given scheduled event.
     *
     * @param  Event  $event  The scheduled event to apply the frequency to.
     * @param  string  $frequency  The frequency string (e.g., 'hourly', 'daily').
     * @return void Returns nothing.
     */
    private function applyFrequency(Event $event, string $frequency): void
    {
        // Apply the appropriate frequency method to the scheduled event based on the provided frequency string
        match ($frequency) {
            'every_fifteen_minutes' => $event->everyFifteenMinutes(),
            'every_thirty_minutes' => $event->everyThirtyMinutes(),
            'daily' => $event->daily(),
            'weekly' => $event->weekly(),
            default => $event->hourly(),
        };
    }
}
