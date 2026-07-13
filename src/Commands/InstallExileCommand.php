<?php

namespace EloquentWorks\Exile\Commands;

use Illuminate\Console\Command;

/**
 * Command to install the Exile package by publishing configuration and migrations.
 */
final class InstallExileCommand extends Command
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'exile:install {--force : Overwrite published files} {--migrate : Run migrations after publishing}';

    /** @var string The console command description. */
    protected $description = 'Publish the Exile configuration and migrations.';

    /**
     * Execute the console command.
     *
     * @return int The exit status code of the command.
     */
    public function handle(): int
    {
        // Determine if the --force option was provided to overwrite existing files
        $force = (bool) $this->option('force');

        // Inform the user that the installation process is starting
        $this->components->info('Installing Laravel Exile...');

        // Publish the Exile configuration file
        $this->call('vendor:publish', [
            '--tag' => 'exile-config',
            '--force' => $force,
        ]);

        // Publish the Exile migration files
        $this->call('vendor:publish', [
            '--tag' => 'exile-migrations',
            '--force' => $force,
        ]);

        // If the --migrate option was provided, run the migrations after publishing
        if ((bool) $this->option('migrate')) {
            $this->call('migrate');
        }

        // Inform the user that the installation process has completed successfully
        $this->components->success('Laravel Exile installed successfully.');

        // Return a success exit code
        return self::SUCCESS;
    }
}
