<?php

namespace EloquentWorks\Exile\Notifications;

final class BanExpiredNotification extends BanNotification
{
    /**
     * Get the array representation of the notification.
     *
     * @param  object  $notifiable  The entity to which the notification is being sent.
     * @return array<string, mixed> The array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        // Return an array representation of the notification, including the ban ID and expiration timestamp.
        return [
            'ban_id' => $this->ban->getKey(),
            'expired_at' => $this->ban
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
        // Return the notification key for this notification, which is used to retrieve configuration settings.
        return 'expired';
    }

    /**
     * Get the default subject for this notification.
     *
     * @return string The default subject.
     */
    protected function defaultSubject(): string
    {
        // Return the default subject for the notification email.
        return 'Enforcement expired';
    }

    /**
     * Get the default view for this notification.
     *
     * @return string The default view.
     */
    protected function defaultView(): string
    {
        // Return the default view for the notification email.
        return 'exile::mail.ban-expired';
    }
}
