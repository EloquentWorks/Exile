<?php

namespace Tests\Unit;

use EloquentWorks\Exile\Enums\WarningSeverity;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WarningTest extends TestCase
{
    #[Test]
    public function it_issues_and_acknowledges_warnings(): void
    {
        // Issue a warning to the user
        $user = $this->user();
        $warning = $user->warn(
            reason: 'Please review the community rules.',
            severity: WarningSeverity::High,
            category: 'abuse',
        );

        // Assert that the warning was issued correctly
        self::assertSame(WarningSeverity::High, $warning->severity);
        self::assertNull($warning->acknowledged_at);
        self::assertTrue($warning->acknowledge());
        self::assertNotNull($warning->refresh()->acknowledged_at);
    }
}
