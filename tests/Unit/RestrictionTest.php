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
        $user = $this->user();
        $restriction = $user->restrict(RestrictionType::ReadOnly, 'Cooling-off period');
        $manager = app(ExileManager::class);

        self::assertTrue($manager->isRestricted($user, RestrictionType::ReadOnly));
        self::assertTrue($manager->isRestricted($user, RestrictionType::Posting));
        self::assertFalse($manager->isRestricted($user, RestrictionType::Login));
        self::assertTrue($manager->activeRestrictionFor($user, RestrictionType::Posting)?->is($restriction));
    }

    #[Test]
    public function shadow_bans_are_detectable_without_being_full_bans(): void
    {
        $user = $this->user();
        $user->restrict(RestrictionType::Shadow, 'Quiet moderation');

        self::assertTrue($user->isShadowBanned());
        self::assertFalse($user->isBanned());
    }

    #[Test]
    public function restrictions_can_be_revoked(): void
    {
        $user = $this->user();
        $restriction = $user->restrict(RestrictionType::Login);

        app(ExileManager::class)->revokeRestriction($restriction);

        self::assertFalse($user->isRestricted(RestrictionType::Login));
    }
}
