<?php

namespace Tests\Unit;

use EloquentWorks\Exile\Enums\BanType;
use EloquentWorks\Exile\Models\Ban;
use EloquentWorks\Exile\Services\ExileManager;
use EloquentWorks\Exile\Support\EnforcementContext;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BanTest extends TestCase
{
    #[Test]
    public function it_issues_and_resolves_an_account_ban(): void
    {
        $user = $this->user();
        $moderator = $this->user('Moderator');

        $ban = $user->ban(
            reason: 'Repeated abuse',
            expiresAt: now()->addDay(),
            moderator: $moderator,
            category: 'abuse',
            internalNotes: 'Case EX-100',
            metadata: ['case' => 'EX-100'],
        );

        self::assertSame(BanType::Account, $ban->type);
        self::assertTrue($ban->isActive());
        self::assertTrue($user->isBanned());
        self::assertSame('Repeated abuse', $ban->reason);
        self::assertSame('EX-100', $ban->metadata['case']);
        self::assertDatabaseHas('exile_actions', ['action' => 'ban.issued']);
    }

    #[Test]
    public function it_revokes_a_ban_and_preserves_history(): void
    {
        $user = $this->user();
        $moderator = $this->user('Moderator');
        $ban = $user->ban(reason: 'Spam');

        app(ExileManager::class)->revokeBan($ban, $moderator);

        self::assertFalse($user->isBanned());
        self::assertNotNull($ban->refresh()->revoked_at);
        self::assertDatabaseHas('exile_actions', ['action' => 'ban.revoked']);
    }

    #[Test]
    public function expired_bans_are_not_active(): void
    {
        $user = $this->user();

        /** @var Ban $ban */
        $ban = Ban::query()->create([
            'type' => BanType::Account,
            'bannable_type' => $user->getMorphClass(),
            'bannable_id' => $user->getKey(),
            'expires_at' => now()->subMinute(),
        ]);

        self::assertTrue($ban->isExpired());
        self::assertFalse($user->isBanned());
        self::assertNull(app(ExileManager::class)->resolveActiveBan(new EnforcementContext($user)));
    }
}
