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
        // The configured thresholds are:
        // 3 points: restrict posting
        // 5 points: restrict posting and commenting
        // 10 points: ban account
        // 15 points: ban account and require appeal
        // 20 points: permanent ban
        // 25 points: permanent ban and require appeal
        $user = $this->user();

        // First strike, 3 points
        $user->strike('Spam burst', points: 3, category: 'spam');

        // Second strike, 2 points
        self::assertSame(3, $user->activeStrikePoints());
        self::assertTrue($user->isRestricted(RestrictionType::Posting));
        self::assertDatabaseHas('exile_actions', ['action' => 'escalation.applied']);
    }

    #[Test]
    public function a_threshold_is_not_applied_twice(): void
    {
        // The configured thresholds are:
        $user = $this->user();

        // First strike, 3 points
        $user->strike('First batch', points: 3);
        $user->strike('Another point', points: 1);

        // Second strike, 2 points
        $count = ModerationAction::query()
            ->where('action', 'escalation.applied')
            ->where('subject_type', $user->getMorphClass())
            ->where('subject_id', $user->getKey())
            ->count();

        // Assert that the escalation action was only applied once
        self::assertSame(1, $count);
    }

    #[Test]
    public function ten_points_trigger_an_account_ban(): void
    {
        // The configured thresholds are:
        $user = $this->user();

        // First strike, 3 points
        $user->strike('Severe repeated abuse', points: 10, category: 'abuse');

        // Assert that the user is banned after accumulating 10 points
        self::assertTrue($user->isBanned());
    }

    #[Test]
    public function strikes_use_the_configured_default_expiration(): void
    {
        // The configured default expiration is 30 days
        Carbon::setTestNow(
            Carbon::parse('2026-07-12 12:00:00')
        );

        // Set the default expiration for strikes to 30 days in the configuration
        config()->set(
            'exile.strikes.expire_after_days',
            30
        );

        // Create a new strike for the user
        $user = $this->user();

        // Create a strike and check that it has the correct expiration date
        $strike = $user->strike(
            reason: 'Temporary strike',
            points: 1
        );

        // Assert that the strike has an expiration date set
        self::assertNotNull($strike->expires_at);

        // Assert that the expiration date is equal to 30 days from now
        self::assertTrue(
            $strike->expires_at->equalTo(
                now()->addDays(30)
            )
        );

        // Reset the test time to avoid affecting other tests
        Carbon::setTestNow();
    }

    #[Test]
    public function an_explicit_strike_expiration_overrides_the_default(): void
    {
        // The configured default expiration is 30 days
        Carbon::setTestNow(
            Carbon::parse('2026-07-12 12:00:00')
        );

        // Set the default expiration for strikes to 30 days in the configuration
        config()->set(
            'exile.strikes.expire_after_days',
            30
        );

        // Create a new strike for the user
        $user = $this->user();

        // Create a strike with an explicit expiration date of 7 days from now
        $strike = $user->strike(
            reason: 'Custom expiration',
            points: 1,
            expiresAt: now()->addDays(7)
        );

        // Assert that the strike has an expiration date set
        self::assertTrue(
            $strike->expires_at->equalTo(
                now()->addDays(7)
            )
        );

        // Reset the test time to avoid affecting other tests
        Carbon::setTestNow();
    }

    #[Test]
    public function strikes_are_permanent_when_no_default_expiration_is_configured(): void
    {
        // The configured default expiration is null (permanent)
        config()->set(
            'exile.strikes.expire_after_days',
            null
        );

        // Create a new strike for the user
        $strike = $this->user()->strike(
            reason: 'Permanent strike',
            points: 1
        );

        // Assert that the strike has no expiration date set (permanent)
        self::assertNull($strike->expires_at);
    }
}
