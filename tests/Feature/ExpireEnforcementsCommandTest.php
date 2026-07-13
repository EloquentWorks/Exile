<?php

namespace Tests\Feature;

use EloquentWorks\Exile\Enums\BanType;
use EloquentWorks\Exile\Enums\RestrictionType;
use EloquentWorks\Exile\Models\Ban;
use EloquentWorks\Exile\Models\Restriction;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpireEnforcementsCommandTest extends TestCase
{
    #[Test]
    public function it_processes_expired_bans_and_restrictions(): void
    {
        $user = $this->user();

        $ban = Ban::query()->create([
            'type' => BanType::Account,
            'bannable_type' => $user->getMorphClass(),
            'bannable_id' => $user->getKey(),
            'reason' => 'Expired test ban',
            'expires_at' => now()->subMinute(),
        ]);

        $restriction = Restriction::query()->create([
            'restrictable_type' => $user->getMorphClass(),
            'restrictable_id' => $user->getKey(),
            'type' => RestrictionType::Posting,
            'reason' => 'Expired test restriction',
            'expires_at' => now()->subMinute(),
        ]);

        $this->artisan('exile:expire', [
            '--chunk' => 1,
        ])
            ->expectsOutputToContain(
                'Processed 1 expired bans and 1 expired restrictions.'
            )
            ->assertSuccessful();

        self::assertNotNull(
            $ban->refresh()->expired_notified_at
        );

        self::assertNotNull(
            $restriction->refresh()->expired_notified_at
        );

        self::assertDatabaseHas('exile_actions', [
            'action' => 'ban.expired',
        ]);

        self::assertDatabaseHas('exile_actions', [
            'action' => 'restriction.expired',
        ]);
    }
}
