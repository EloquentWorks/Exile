<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstallCommandTest extends TestCase
{
    #[Test]
    public function the_install_command_completes_successfully(): void
    {
        $this->artisan('exile:install', [
            '--force' => true,
        ])
            ->expectsOutputToContain(
                'Installing Laravel Exile'
            )
            ->expectsOutputToContain(
                'Laravel Exile installed successfully'
            )
            ->assertSuccessful();
    }

    #[Test]
    public function the_install_command_can_run_migrations(): void
    {
        $this->artisan('exile:install', [
            '--force' => true,
            '--migrate' => true,
        ])->assertSuccessful();

        self::assertTrue(
            Schema::hasTable('exile_bans')
        );

        self::assertTrue(
            Schema::hasTable('exile_restrictions')
        );

        self::assertTrue(
            Schema::hasTable('exile_strikes')
        );

        self::assertTrue(
            Schema::hasTable('exile_warnings')
        );

        self::assertTrue(
            Schema::hasTable('exile_appeals')
        );

        self::assertTrue(
            Schema::hasTable('exile_evidence')
        );

        self::assertTrue(
            Schema::hasTable('exile_device_fingerprints')
        );

        self::assertTrue(
            Schema::hasTable('exile_actions')
        );
    }
}
