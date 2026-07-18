<?php

namespace EloquentWorks\Exile\Notifications;

/**
 * Notification sent when a ban is issued.
 */
final class BanIssuedNotification extends BanNotification
{
    /**
     * Get the array representation of the notification.
     *
     * @param  object  $notifiable  The entity to which the notification is being sent.
     * @return array<string, mixed> The array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        // Return an array representation of the notification, including the ban ID, type, reason, and expiration timestamp.
        return [
            'ban_id' => $this->ban->getKey(),
            'type' => $this->ban->type->value,
            'reason' => $this->ban->reason,
            'expires_at' => $this->ban
                ->expires_at
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
        return 'issued';
    }

    /**
     * Get the default subject for this notification.
     *
     * @return string The default subject.
     */
    protected function defaultSubject(): string
    {
        return 'Account enforcement notice';
    }

    /**
     * Get the default view for this notification.
     *
     * @return string The default view.
     */
    protected function defaultView(): string
    {
        return 'exile::mail.ban-issued';
    }
}
