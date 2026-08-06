<?php

namespace Tests\Unit;

use EloquentWorks\Exile\Enums\AppealStatus;
use EloquentWorks\Exile\Services\ExileManager;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AppealTest extends TestCase
{
    #[Test]
    public function users_can_appeal_and_an_approved_appeal_revokes_the_ban(): void
    {
        // Arrange
        $user = $this->user();
        $reviewer = $this->user('Reviewer');
        $ban = $user->ban('Disputed enforcement');
        $manager = app(ExileManager::class);

        // Act
        $appeal = $manager->submitAppeal($ban, $user, 'I believe this was issued in error.');

        // Assert
        self::assertSame(AppealStatus::Pending, $appeal->status);
        self::assertTrue($manager->resolveAppeal($appeal, AppealStatus::Approved, $reviewer, 'Appeal accepted.'));
        self::assertSame(AppealStatus::Approved, $appeal->refresh()->status);
        self::assertFalse($user->isBanned());
    }

    #[Test]
    public function duplicate_pending_appeals_are_rejected_by_default(): void
    {
        // Arrange
        $user = $this->user();
        $ban = $user->ban();
        $manager = app(ExileManager::class);
        $manager->submitAppeal($ban, $user, 'First appeal');

        // Act & Assert
        $this->expectException(LogicException::class);

        // Attempt to submit a second appeal while the first is still pending
        $manager->submitAppeal($ban, $user, 'Second appeal');
    }
}
