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
        // Fake the notification system to prevent actual notifications from being sent.
        Notification::fake();

        // Enable notifications and issued notifications in the configuration.
        config()->set(
            'exile.notifications.enabled',
            true
        );

        // Enable issued notifications in the configuration.
        config()->set(
            'exile.notifications.issued',
            true
        );

        // Create a user and issue a ban to trigger the notification.
        $user = $this->user();

        // Issue a ban for the user with a reason.
        $user->ban(
            reason: 'Notification test'
        );

        // Assert that the BanIssuedNotification was sent to the user.
        Notification::assertSentTo(
            $user,
            BanIssuedNotification::class
        );
    }

    #[Test]
    public function notifications_are_not_sent_when_globally_disabled(): void
    {
        // Fake the notification system to prevent actual notifications from being sent.
        Notification::fake();

        // Disable notifications in the configuration.
        config()->set(
            'exile.notifications.enabled',
            false
        );

        // Issue a ban for the user with a reason.
        $this->user()->ban(
            reason: 'No notification'
        );

        // Assert that no notifications were sent.
        Notification::assertNothingSent();
    }

    #[Test]
    public function issued_notifications_can_be_disabled_individually(): void
    {
        // Fake the notification system to prevent actual notifications from being sent.
        Notification::fake();

        // Enable notifications in the configuration.
        config()->set(
            'exile.notifications.enabled',
            true
        );

        // Disable issued notifications in the configuration.
        config()->set(
            'exile.notifications.issued',
            false
        );

        // Issue a ban for the user with a reason.
        $this->user()->ban(
            reason: 'No issued notification'
        );

        // Assert that no notifications were sent.
        Notification::assertNothingSent();
    }
}
