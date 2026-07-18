<?php

namespace EloquentWorks\Exile\Notifications;

use EloquentWorks\Exile\Models\Ban;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

/**
 * Base class for ban-related notifications.
 */
abstract class BanNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  Ban  $ban  The ban associated with this notification.
     */
    public function __construct(
        public readonly Ban $ban
    ) {
        $this->afterCommit();
    }

    /**
     * Get the configured delivery channels.
     *
     * @param  object  $notifiable  The entity to which the notification is being sent.
     * @return list<string> The channels through which the notification will be sent.
     */
    public function via(object $notifiable): array
    {
        // Get the configured notification channels from the Exile configuration.
        $channels = config(
            'exile.notifications.channels',
            ['mail']
        );

        // Normalize the channels to ensure they are valid strings and filter out any empty values.
        if (! is_array($channels)) {
            return ['mail'];
        }

        /** @var list<string> $normalized */
        $normalized = array_values(
            array_filter(
                $channels,
                static fn (mixed $channel): bool => is_string($channel)
                    && $channel !== ''
            )
        );

        // If no valid channels are configured, default to the 'mail' channel.
        return $normalized;
    }

    /**
     * Build the configurable Markdown mail message.
     *
     * @param  object  $notifiable  The entity to which the notification is being sent.
     * @return MailMessage The configured mail message.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Get the mail configuration for this notification.
        $mail = $this->mailConfiguration();

        // Determine the subject and view for the mail message, falling back to defaults if not configured.
        $subject = $mail['subject']
            ?? $this->defaultSubject();

        // Determine the view for the mail message, falling back to the default view if not configured.
        $view = $mail['view']
            ?? $this->defaultView();

        // Validate that the subject and view are non-empty strings, throwing an exception if they are not.
        if (
            ! is_string($subject)
            || $subject === ''
        ) {
            throw new InvalidArgumentException(
                'The configured Exile notification subject must be a non-empty string.'
            );
        }

        // Validate that the view is a non-empty string, throwing an exception if it is not.
        if (
            ! is_string($view)
            || $view === ''
        ) {
            throw new InvalidArgumentException(
                'The configured Exile notification view must be a non-empty string.'
            );
        }

        // Build and return the mail message using the determined subject, view, and additional data.
        return (new MailMessage)
            ->subject($subject)
            ->markdown(
                $view,
                [
                    'ban' => $this->ban,
                    'notifiable' => $notifiable,
                    'mail' => $mail,
                    'showReason' => (bool) config(
                        'exile.responses.include_reason',
                        true
                    ),
                    'showExpiration' => (bool) config(
                        'exile.responses.include_expiration',
                        true
                    ),
                    'formattedExpiration' => $this->formattedExpiration(),
                ]
            );
    }

    /**
     * Get the mail configuration for this notification.
     *
     * @return array<string, mixed> The mail configuration for this notification.
     */
    protected function mailConfiguration(): array
    {
        // Retrieve the mail configuration for this specific notification key from the Exile configuration.
        $configuration = config(
            'exile.notifications.mail.'
            .$this->notificationKey(),
            []
        );

        // Ensure that the configuration is an array, returning an empty array if it is not.
        return is_array($configuration)
            ? $configuration
            : [];
    }

    /**
     * Get the formatted expiration date for the ban.
     *
     * @return string|null The formatted expiration date, or null if the ban does not expire.
     */
    protected function formattedExpiration(): ?string
    {
        // If the ban does not have an expiration date, return null.
        if ($this->ban->expires_at === null) {
            return null;
        }

        // Get a copy of the expiration date to avoid modifying the original.
        $expiresAt = $this->ban->expires_at->copy();

        // Get the configured timezone for notifications from the Exile configuration.
        $timezone = config(
            'exile.notifications.mail.timezone'
        );

        // If a valid timezone is configured, convert the expiration date to that timezone.
        if (
            is_string($timezone)
            && $timezone !== ''
        ) {
            // Convert the expiration date to the configured timezone.
            $expiresAt = $expiresAt->timezone(
                $timezone
            );
        }

        // Get the configured date format for notifications from the Exile configuration.
        $format = config(
            'exile.notifications.mail.date_format',
            'M j, Y g:i A T'
        );

        // Format the expiration date using the configured format, falling back to a default format if not configured.
        return $expiresAt->format(
            is_string($format) && $format !== ''
                ? $format
                : 'M j, Y g:i A T'
        );
    }

    /**
     * Get the notification key for this specific notification type.
     *
     * @return string The notification key used to retrieve configuration settings.
     */
    abstract protected function notificationKey(): string;

    /**
     * Get the default subject for the mail message.
     *
     * @return string The default subject for the mail message.
     */
    abstract protected function defaultSubject(): string;

    /**
     * Get the default view for the mail message.
     *
     * @return string The default view for the mail message.
     */
    abstract protected function defaultView(): string;
}
