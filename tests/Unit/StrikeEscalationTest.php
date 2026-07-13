<?php

namespace Tests\Unit;

use EloquentWorks\Exile\Enums\RestrictionType;
use EloquentWorks\Exile\Models\ModerationAction;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class StrikeEscalationTest extends TestCase
{
    #[Test]
    public function strikes_accumulate_points_and_trigger_the_highest_reached_threshold(): void
    {
        $user = $this->user();

        $user->strike('Spam burst', points: 3, category: 'spam');

        self::assertSame(3, $user->activeStrikePoints());
        self::assertTrue($user->isRestricted(RestrictionType::Posting));
        self::assertDatabaseHas('exile_actions', ['action' => 'escalation.applied']);
    }

    #[Test]
    public function a_threshold_is_not_applied_twice(): void
    {
        $user = $this->user();

        $user->strike('First batch', points: 3);
        $user->strike('Another point', points: 1);

        $count = ModerationAction::query()
            ->where('action', 'escalation.applied')
            ->where('subject_type', $user->getMorphClass())
            ->where('subject_id', $user->getKey())
            ->count();

        self::assertSame(1, $count);
    }

    #[Test]
    public function ten_points_trigger_an_account_ban(): void
    {
        $user = $this->user();

        $user->strike('Severe repeated abuse', points: 10, category: 'abuse');

        self::assertTrue($user->isBanned());
    }

    #[Test]
    public function strikes_use_the_configured_default_expiration(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-07-12 12:00:00')
        );

        config()->set(
            'exile.strikes.expire_after_days',
            30
        );

        $user = $this->user();

        $strike = $user->strike(
            reason: 'Temporary strike',
            points: 1
        );

        self::assertNotNull($strike->expires_at);

        self::assertTrue(
            $strike->expires_at->equalTo(
                now()->addDays(30)
            )
        );

        Carbon::setTestNow();
    }

    #[Test]
    public function an_explicit_strike_expiration_overrides_the_default(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-07-12 12:00:00')
        );

        config()->set(
            'exile.strikes.expire_after_days',
            30
        );

        $user = $this->user();

        $strike = $user->strike(
            reason: 'Custom expiration',
            points: 1,
            expiresAt: now()->addDays(7)
        );

        self::assertTrue(
            $strike->expires_at->equalTo(
                now()->addDays(7)
            )
        );

        Carbon::setTestNow();
    }

    #[Test]
    public function strikes_are_permanent_when_no_default_expiration_is_configured(): void
    {
        config()->set(
            'exile.strikes.expire_after_days',
            null
        );

        $strike = $this->user()->strike(
            reason: 'Permanent strike',
            points: 1
        );

        self::assertNull($strike->expires_at);
    }
}
