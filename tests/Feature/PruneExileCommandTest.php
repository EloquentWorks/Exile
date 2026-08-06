<?php

namespace Tests\Feature;

use EloquentWorks\Exile\Services\ExileManager;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PruneExileCommandTest extends TestCase
{
    #[Test]
    public function pruning_does_nothing_when_disabled(): void
    {
        // Disable pruning in the configuration.
        config()->set(
            'exile.retention.prune_enabled',
            false
        );

        // Run the artisan command to prune exile records.
        $user = $this->user();

        // Create a ban for the user with a reason.
        $ban = $user->ban(
            reason: 'Old revoked ban'
        );

        // Revoke the ban using the ExileManager service.
        app(ExileManager::class)->revokeBan($ban);

        // Update the 'updated_at' timestamp of the ban to simulate it being old (400 days ago).
        DB::table($ban->getTable())
            ->where('id', $ban->getKey())
            ->update([
                'updated_at' => now()->subDays(400),
            ]);

        // Run the artisan command to prune exile records with a specified number of days.
        $this->artisan('exile:prune', [
            '--days' => 365,
        ])->assertSuccessful();

        // Assert that the ban still exists in the database since pruning is disabled.
        self::assertDatabaseHas(
            $ban->getTable(),
            ['id' => $ban->getKey()]
        );
    }

    #[Test]
    public function force_pruning_removes_old_revoked_bans(): void
    {
        // Get the user instance for testing.
        $user = $this->user();

        // Create a ban for the user with a reason.
        $ban = $user->ban(
            reason: 'Old revoked ban'
        );

        // Revoke the ban using the ExileManager service.
        app(ExileManager::class)->revokeBan($ban);

        // Update the 'updated_at' timestamp of the ban to simulate it being old (400 days ago).
        DB::table($ban->getTable())
            ->where('id', $ban->getKey())
            ->update([
                'updated_at' => now()->subDays(400),
            ]);

        // Run the artisan command to prune exile records with the --force option and a specified number of days.
        $this->artisan('exile:prune', [
            '--force' => true,
            '--days' => 365,
        ])->assertSuccessful();

        // Assert that the ban has been removed from the database since it was old and revoked.
        self::assertDatabaseMissing(
            $ban->getTable(),
            ['id' => $ban->getKey()]
        );
    }
}
