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
        config()->set(
            'exile.retention.prune_enabled',
            false
        );

        $user = $this->user();

        $ban = $user->ban(
            reason: 'Old revoked ban'
        );

        app(ExileManager::class)->revokeBan($ban);

        DB::table($ban->getTable())
            ->where('id', $ban->getKey())
            ->update([
                'updated_at' => now()->subDays(400),
            ]);

        $this->artisan('exile:prune', [
            '--days' => 365,
        ])->assertSuccessful();

        self::assertDatabaseHas(
            $ban->getTable(),
            ['id' => $ban->getKey()]
        );
    }

    #[Test]
    public function force_pruning_removes_old_revoked_bans(): void
    {
        $user = $this->user();

        $ban = $user->ban(
            reason: 'Old revoked ban'
        );

        app(ExileManager::class)->revokeBan($ban);

        DB::table($ban->getTable())
            ->where('id', $ban->getKey())
            ->update([
                'updated_at' => now()->subDays(400),
            ]);

        $this->artisan('exile:prune', [
            '--force' => true,
            '--days' => 365,
        ])->assertSuccessful();

        self::assertDatabaseMissing(
            $ban->getTable(),
            ['id' => $ban->getKey()]
        );
    }
}
