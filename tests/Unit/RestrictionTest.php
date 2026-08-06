<?php

namespace Tests\Unit;

use EloquentWorks\Exile\Enums\RestrictionType;
use EloquentWorks\Exile\Services\ExileManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RestrictionTest extends TestCase
{
    #[Test]
    public function read_only_restrictions_also_block_posting(): void
    {
        // A read-only restriction should also block posting, but not login.
        $user = $this->user();
        $restriction = $user->restrict(RestrictionType::ReadOnly, 'Cooling-off period');
        $manager = app(ExileManager::class);

        // Assert that the user is restricted for both read-only and posting, but not for login.
        self::assertTrue($manager->isRestricted($user, RestrictionType::ReadOnly));
        self::assertTrue($manager->isRestricted($user, RestrictionType::Posting));
        self::assertFalse($manager->isRestricted($user, RestrictionType::Login));
        self::assertTrue($manager->activeRestrictionFor($user, RestrictionType::Posting)?->is($restriction));
    }

    #[Test]
    public function shadow_bans_are_detectable_without_being_full_bans(): void
    {
        // A shadow ban should be detectable, but it should not be considered a full ban.
        $user = $this->user();
        $user->restrict(RestrictionType::Shadow, 'Quiet moderation');

        // Assert that the user is shadow banned, but not fully banned.
        self::assertTrue($user->isShadowBanned());
        self::assertFalse($user->isBanned());
    }

    #[Test]
    public function restrictions_can_be_revoked(): void
    {
        // A restriction can be revoked, and the user should no longer be restricted after revocation.
        $user = $this->user();
        $restriction = $user->restrict(RestrictionType::Login);

        // Assert that the user is restricted for login.
        app(ExileManager::class)->revokeRestriction($restriction);

        // Assert that the user is no longer restricted for login.
        self::assertFalse($user->isRestricted(RestrictionType::Login));
    }
}
