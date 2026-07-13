<?php

namespace EloquentWorks\Exile\Notifications;

use EloquentWorks\Exile\Models\Ban;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a ban is issued.
 */
final class BanIssuedNotification extends Notification
{
    /**
     * Create a new notification instance.
     *
     * @param  Ban  $ban  The ban that was issued.
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

        // If the notifiable entity has a valid email address, include the 'mail' channel for email notifications.
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
        // Create a new mail message with the subject and body content based on the ban details and configuration.
        $message = (new MailMessage)
            ->subject('Account enforcement notice')
            ->line((string) config('exile.responses.ban_message', 'Your access has been suspended.'));

        // Include the ban reason in the email if it is not null.
        if ($this->ban->reason !== null) {
            $message->line('Reason: '.$this->ban->reason);
        }

        // Include the ban expiration time in the email, indicating whether it is permanent or providing the expiration date and time.
        $message->line($this->ban->expires_at === null
            ? 'This enforcement is permanent.'
            : 'Expires: '.$this->ban->expires_at->toDateTimeString());

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  object  $notifiable  The entity to which the notification is being sent.
     * @return array<string, mixed> The array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        // Return an array representation of the notification, including the ban ID, type, reason, and expiration time.
        return [
            'ban_id' => $this->ban->getKey(),
            'type' => $this->ban->type->value,
            'reason' => $this->ban->reason,
            'expires_at' => $this->ban->expires_at?->toISOString(),
        ];
    }
}
