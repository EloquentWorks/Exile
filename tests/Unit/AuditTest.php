<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditTest extends TestCase
{
    #[Test]
    public function moderation_actions_are_recorded_when_auditing_is_enabled(): void
    {
        // Enable auditing for this test
        config()->set(
            'exile.audit.enabled',
            true
        );

        // Perform a moderation action (e.g., ban a user)
        $this->user()->ban(
            reason: 'Audit test'
        );

        // Assert that the moderation action was recorded in the database
        self::assertDatabaseHas('exile_actions', [
            'action' => 'ban.issued',
        ]);
    }

    #[Test]
    public function moderation_actions_are_not_recorded_when_auditing_is_disabled(): void
    {
        // Disable auditing for this test
        config()->set(
            'exile.audit.enabled',
            false
        );

        // Perform a moderation action (e.g., ban a user)
        $this->user()->ban(
            reason: 'No audit test'
        );

        // Assert that no moderation actions were recorded in the database
        self::assertDatabaseCount(
            'exile_actions',
            0
        );
    }
}
