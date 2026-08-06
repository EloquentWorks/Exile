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
        // Create a user to ban and restrict
        $user = $this->user();

        // Create an expired ban and restriction for the user
        $ban = Ban::query()->create([
            'type' => BanType::Account,
            'bannable_type' => $user->getMorphClass(),
            'bannable_id' => $user->getKey(),
            'reason' => 'Expired test ban',
            'expires_at' => now()->subMinute(),
        ]);

        // Create an expired restriction for the user
        $restriction = Restriction::query()->create([
            'restrictable_type' => $user->getMorphClass(),
            'restrictable_id' => $user->getKey(),
            'type' => RestrictionType::Posting,
            'reason' => 'Expired test restriction',
            'expires_at' => now()->subMinute(),
        ]);

        // Run the artisan command to process expired bans and restrictions
        $this->artisan('exile:expire', [
            '--chunk' => 1,
        ])
            ->expectsOutputToContain(
                'Processed 1 expired bans and 1 expired restrictions.'
            )
            ->assertSuccessful();

        // Assert that the expired_notified_at timestamp is set for the ban
        self::assertNotNull(
            $ban->refresh()->expired_notified_at
        );

        // Assert that the expired_notified_at timestamp is set for the restriction
        self::assertNotNull(
            $restriction->refresh()->expired_notified_at
        );

        // Assert that the audit log contains entries for the expired ban and restriction
        self::assertDatabaseHas('exile_actions', [
            'action' => 'ban.expired',
        ]);

        // Assert that the audit log contains entries for the expired restriction
        self::assertDatabaseHas('exile_actions', [
            'action' => 'restriction.expired',
        ]);
    }
}
