<?php

namespace Tests\Unit;

use EloquentWorks\Exile\Enums\BanType;
use EloquentWorks\Exile\Models\Ban;
use EloquentWorks\Exile\Services\ExileManager;
use EloquentWorks\Exile\Support\EnforcementContext;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BanTest extends TestCase
{
    #[Test]
    public function it_rejects_an_unconfigured_category(): void
    {
        // Expect an InvalidArgumentException to be thrown when trying to ban a user with an unconfigured category.
        $this->expectException(
            InvalidArgumentException::class
        );

        // Expect the exception message to indicate that the supplied enforcement category is not configured.
        $this->expectExceptionMessage(
            'The supplied enforcement category is not configured.'
        );

        // Attempt to ban the user with an invalid category, which should trigger the exception.
        $this->user()->ban(
            reason: 'Invalid category',
            category: 'not-configured'
        );
    }

    #[Test]
    public function it_rejects_an_expiration_date_in_the_past(): void
    {
        // Expect an InvalidArgumentException to be thrown when trying to ban a user with an expiration date in the past.
        $this->expectException(
            InvalidArgumentException::class
        );

        // Expect the exception message to indicate that the expiration date must be in the future.
        $this->expectExceptionMessage(
            'The expiration date must be in the future.'
        );

        // Attempt to ban the user with an expiration date in the past, which should trigger the exception.
        $this->user()->ban(
            reason: 'Invalid expiration',
            expiresAt: now()->subMinute()
        );
    }

    #[Test]
    public function it_issues_and_resolves_an_account_ban(): void
    {
        // Create a user and a moderator for the test.
        $user = $this->user();
        $moderator = $this->user('Moderator');

        // Issue a ban on the user with specific parameters, including reason, expiration date,
        // moderator, category, internal notes, and metadata.
        $ban = $user->ban(
            reason: 'Repeated abuse',
            expiresAt: now()->addDay(),
            moderator: $moderator,
            category: 'abuse',
            internalNotes: 'Case EX-100',
            metadata: ['case' => 'EX-100'],
        );

        // Assert that the ban has been issued correctly and that the user's ban status is updated.
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
        // Create a user and a moderator for the test.
        $user = $this->user();
        $moderator = $this->user('Moderator');
        $ban = $user->ban(reason: 'Spam');

        // Revoke the ban using the ExileManager service, passing in the ban and moderator.
        app(ExileManager::class)->revokeBan($ban, $moderator);

        // Assert that the ban has been revoked, the user's ban status is updated, and the revocation timestamp is recorded.
        self::assertFalse($user->isBanned());
        self::assertNotNull($ban->refresh()->revoked_at);
        self::assertDatabaseHas('exile_actions', ['action' => 'ban.revoked']);
    }

    #[Test]
    public function expired_bans_are_not_active(): void
    {
        // Create a user and issue a ban that has already expired.
        $user = $this->user();

        /** @var Ban $ban */
        $ban = Ban::query()->create([
            'type' => BanType::Account,
            'bannable_type' => $user->getMorphClass(),
            'bannable_id' => $user->getKey(),
            'expires_at' => now()->subMinute(),
        ]);

        // Assert that the ban is marked as expired, the user's ban status is updated,
        // and there is no active ban resolved for the user.
        self::assertTrue($ban->isExpired());
        self::assertFalse($user->isBanned());
        self::assertNull(app(ExileManager::class)->resolveActiveBan(new EnforcementContext($user)));
    }
}
