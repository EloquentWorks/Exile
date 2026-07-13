<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditTest extends TestCase
{
    #[Test]
    public function moderation_actions_are_recorded_when_auditing_is_enabled(): void
    {
        config()->set(
            'exile.audit.enabled',
            true
        );

        $this->user()->ban(
            reason: 'Audit test'
        );

        self::assertDatabaseHas('exile_actions', [
            'action' => 'ban.issued',
        ]);
    }

    #[Test]
    public function moderation_actions_are_not_recorded_when_auditing_is_disabled(): void
    {
        config()->set(
            'exile.audit.enabled',
            false
        );

        $this->user()->ban(
            reason: 'No audit test'
        );

        self::assertDatabaseCount(
            'exile_actions',
            0
        );
    }
}
