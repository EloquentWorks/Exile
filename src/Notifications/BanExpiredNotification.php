<?php

namespace EloquentWorks\Exile\Notifications;

use EloquentWorks\Exile\Models\Ban;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a ban expires.
 */
final class BanExpiredNotification extends Notification
{
    /**
     * Create a new notification instance.
     *
     * @param  Ban  $ban  The ban that has expired.
     */
    public function __construct(public readonly Ban $ban) {}

    /**
     * Get the notification's delivery channels.
     *
     * @param  object  $notifiable  The entity to which the notification is being sent.
     * @return array<int, string> The channels through which the notification will be sent.
     */
    public function via(object $notifiable): array
    {
        /** @var list<string> $channels */
        $channels = config('exile.notifications.channels', ['mail']);

        // If the notifiable entity has a custom notification channel, use it instead
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  object  $notifiable  The entity to which the notification is being sent.
     * @return MailMessage The mail message to be sent.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Enforcement expired')
            ->line('Your temporary account enforcement has expired.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  object  $notifiable  The entity to which the notification is being sent.
     * @return array<string, mixed> The array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return ['ban_id' => $this->ban->getKey(), 'expired_at' => $this->ban->expires_at?->toISOString()];
    }
}
