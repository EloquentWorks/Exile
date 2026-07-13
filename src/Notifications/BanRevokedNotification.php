<?php

namespace EloquentWorks\Exile\Notifications;

use EloquentWorks\Exile\Models\Ban;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a ban is revoked.
 */
final class BanRevokedNotification extends Notification
{
    /**
     * Create a new notification instance.
     *
     * @param  Ban  $ban  The ban that has been revoked.
     */
    public function __construct(public readonly Ban $ban) {}

    /**
     * Get the notification's delivery channels.
     *
     * @param  object  $notifiable  The entity to be notified.
     * @return array<int, string> The channels through which the notification will be sent.
     */
    public function via(object $notifiable): array
    {
        /** @var list<string> $channels */
        $channels = config('exile.notifications.channels', ['mail']);

        // You can customize the channels based on the notifiable entity or other conditions if needed.
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  object  $notifiable  The entity to be notified.
     * @return MailMessage The mail message to be sent.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // You can customize the email content here. For example, you might want to include details about the ban that was revoked.
        return (new MailMessage)
            ->subject('Enforcement revoked')
            ->line('Your account enforcement has been revoked.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  object  $notifiable  The entity to be notified.
     * @return array<string, mixed> The array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        // You can customize the array representation here. For example, you might want to include details about the ban that was revoked.
        return ['ban_id' => $this->ban->getKey(), 'revoked_at' => $this->ban->revoked_at?->toISOString()];
    }
}
