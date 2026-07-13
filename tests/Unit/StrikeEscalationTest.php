<?php

namespace Tests\Unit;

use EloquentWorks\Exile\Enums\RestrictionType;
use EloquentWorks\Exile\Models\ModerationAction;
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
}
