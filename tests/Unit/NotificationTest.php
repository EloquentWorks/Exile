<?php

namespace Tests\Unit;

use EloquentWorks\Exile\Notifications\BanIssuedNotification;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    #[Test]
    public function an_issued_ban_notification_is_sent_when_enabled(): void
    {
        Notification::fake();

        config()->set(
            'exile.notifications.enabled',
            true
        );

        config()->set(
            'exile.notifications.issued',
            true
        );

        $user = $this->user();

        $user->ban(
            reason: 'Notification test'
        );

        Notification::assertSentTo(
            $user,
            BanIssuedNotification::class
        );
    }

    #[Test]
    public function notifications_are_not_sent_when_globally_disabled(): void
    {
        Notification::fake();

        config()->set(
            'exile.notifications.enabled',
            false
        );

        $this->user()->ban(
            reason: 'No notification'
        );

        Notification::assertNothingSent();
    }

    #[Test]
    public function issued_notifications_can_be_disabled_individually(): void
    {
        Notification::fake();

        config()->set(
            'exile.notifications.enabled',
            true
        );

        config()->set(
            'exile.notifications.issued',
            false
        );

        $this->user()->ban(
            reason: 'No issued notification'
        );

        Notification::assertNothingSent();
    }
}
