<?php

namespace EloquentWorks\Exile\Commands;

use Illuminate\Console\Command;

/**
 * Command to install Laravel Exile by publishing configuration, migrations, and optional views.
 */
final class InstallExileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exile:install
        {--force : Overwrite published files}
        {--migrate : Run migrations after publishing}
        {--views : Publish customizable notification mail templates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the Exile configuration, migrations, and optional notification views.';

    /**
     * Execute the console command.
     *
     * @return int The exit code of the command.
     */
    public function handle(): int
    {
        // Determine if the --force option was provided to overwrite existing files
        $force = (bool) $this->option('force');

        // Display an informational message indicating the start of the installation process
        $this->components->info(
            'Installing Laravel Exile...'
        );

        // Publish the configuration file for the Exile package
        $this->call('vendor:publish', [
            '--tag' => 'exile-config',
            '--force' => $force,
        ]);

        // Publish the migration files for the Exile package
        $this->call('vendor:publish', [
            '--tag' => 'exile-migrations',
            '--force' => $force,
        ]);

        // If the --views option is provided, publish the customizable notification mail templates
        if ((bool) $this->option('views')) {
            $this->call('vendor:publish', [
                '--tag' => 'exile-views',
                '--force' => $force,
            ]);
        }

        // If the --migrate option is provided, run the migrations after publishing
        if ((bool) $this->option('migrate')) {
            $this->call('migrate');
        }

        // Display a success message indicating that Laravel Exile has been installed successfully
        $this->components->success(
            'Laravel Exile installed successfully.'
        );

        // Return a success exit code to indicate that the command executed successfully
        return self::SUCCESS;
    }
}
