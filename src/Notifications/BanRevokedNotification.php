<?php

namespace EloquentWorks\Exile\Notifications;

/**
 * Notification sent when a ban is revoked.
 */
final class BanRevokedNotification extends BanNotification
{
    /**
     * Get the array representation of the notification.
     *
     * @param  object  $notifiable  The entity to which the notification is being sent.
     * @return array<string, mixed> The array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        // Return an array representation of the notification, including the ban ID and revocation timestamp.
        return [
            'ban_id' => $this->ban->getKey(),
            'revoked_at' => $this->ban
                ->revoked_at
                ?->toISOString(),
        ];
    }

    /**
     * Get the notification key for this notification.
     *
     * @return string The notification key used to retrieve configuration settings.
     */
    protected function notificationKey(): string
    {
        return 'revoked';
    }

    /**
     * Get the default subject for this notification.
     *
     * @return string The default subject.
     */
    protected function defaultSubject(): string
    {
        return 'Enforcement revoked';
    }

    /**
     * Get the default view for this notification.
     *
     * @return string The default view.
     */
    protected function defaultView(): string
    {
        return 'exile::mail.ban-revoked';
    }
}
