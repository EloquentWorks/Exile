<?php

namespace EloquentWorks\Exile\Services;

use EloquentWorks\Exile\Models\Ban;
use EloquentWorks\Exile\Notifications\BanExpiredNotification;
use EloquentWorks\Exile\Notifications\BanIssuedNotification;
use EloquentWorks\Exile\Notifications\BanRevokedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use LogicException;
use Throwable;

/**
 * Class NotificationDispatcher
 *
 * This class is responsible for dispatching notifications related to bans.
 * It checks the configuration settings to determine whether to send notifications
 * for issued, revoked, or expired bans and sends them to the appropriate recipients.
 */
final class NotificationDispatcher
{
    /**
     * Dispatch a notification when a ban is issued.
     *
     * @param  Ban  $ban  The ban that has been issued.
     */
    public function banIssued(Ban $ban): void
    {
        // Check if notifications for issued bans are enabled in the configuration.
        if (
            // If notifications for issued bans are enabled, proceed to send the notification.
            config(
                'exile.notifications.issued',
                true
            )
        ) {
            // Send the notification to the configured recipient using the sendConfigured method.
            $this->sendConfigured(
                recipient: $ban->bannable,
                key: 'issued',
                defaultClass: BanIssuedNotification::class,
                ban: $ban,
            );
        }
    }

    /**
     * Dispatch a notification when a ban is revoked.
     *
     * @param  Ban  $ban  The ban that has been revoked.
     */
    public function banRevoked(Ban $ban): void
    {
        // Check if notifications for revoked bans are enabled in the configuration.
        if (
            // If notifications for revoked bans are enabled, proceed to send the notification.
            config(
                'exile.notifications.revoked',
                true
            )
        ) {
            // Send the notification to the configured recipient using the sendConfigured method.
            $this->sendConfigured(
                recipient: $ban->bannable,
                key: 'revoked',
                defaultClass: BanRevokedNotification::class,
                ban: $ban,
            );
        }
    }

    /**
     * Dispatch a notification when a ban has expired.
     *
     * @param  Ban  $ban  The ban that has expired.
     */
    public function banExpired(Ban $ban): void
    {
        // Check if notifications for expired bans are enabled in the configuration.
        if (
            // If notifications for expired bans are enabled, proceed to send the notification.
            config(
                'exile.notifications.expired',
                true
            )
        ) {
            // Send the notification to the configured recipient using the sendConfigured method.
            $this->sendConfigured(
                recipient: $ban->bannable,
                key: 'expired',
                defaultClass: BanExpiredNotification::class,
                ban: $ban,
            );
        }
    }

    /**
     * Send a notification to the configured recipient if notifications are enabled.
     *
     * @param  Model|null  $recipient  The recipient of the notification.
     * @param  string  $key  The key used to retrieve the notification class from configuration.
     * @param  string  $defaultClass  The default notification class to use if not configured.
     * @param  Ban  $ban  The ban associated with the notification.
     */
    private function sendConfigured(
        ?Model $recipient,
        string $key,
        string $defaultClass,
        Ban $ban,
    ): void {
        // Check if notifications are enabled in the configuration and if the recipient is not null.
        if (
            ! config(
                'exile.notifications.enabled',
                false
            )
            || $recipient === null
        ) {
            return;
        }

        // Check if the recipient has the necessary methods to receive notifications.
        if (
            ! method_exists(
                $recipient,
                'routeNotificationFor'
            )
            && ! method_exists(
                $recipient,
                'notify'
            )
        ) {
            return;
        }

        try {
            // Create the notification instance using the configured or default class.
            $notification = $this->makeNotification(
                $key,
                $defaultClass,
                $ban
            );

            // Send the notification to the recipient.
            NotificationFacade::send(
                $recipient,
                $notification
            );
        } catch (Throwable $exception) {
            // If an exception occurs and fail_silently is false, rethrow the exception.
            if (
                ! config(
                    'exile.notifications.fail_silently',
                    true
                )
            ) {
                throw $exception;
            }

            // Otherwise, log the exception for debugging purposes.
            report($exception);
        }
    }

    /**
     * Create a notification instance based on the configured class or default class.
     *
     * @param  string  $key  The key used to retrieve the notification class from configuration.
     * @param  string  $defaultClass  The default notification class to use if not configured.
     * @param  Ban  $ban  The ban associated with the notification.
     * @return Notification The created notification instance.
     *
     * @throws LogicException If the configured class does not extend the Notification class.
     */
    private function makeNotification(
        string $key,
        string $defaultClass,
        Ban $ban,
    ): Notification {
        // Retrieve the configured notification class from the configuration, falling back to the default class if not set.
        $configuredClass = config(
            "exile.notifications.classes.{$key}",
            $defaultClass
        );

        // Validate that the configured class is a string and extends the Notification class.
        if (
            ! is_string($configuredClass)
            || ! is_a(
                $configuredClass,
                Notification::class,
                true
            )
        ) {
            // If the configured class is invalid, throw a LogicException with a descriptive message.
            throw new LogicException(
                "The configured Exile notification class for [{$key}] must extend "
                .Notification::class.'.'
            );
        }

        /** @var Notification $notification */
        $notification = app()->makeWith(
            $configuredClass,
            ['ban' => $ban]
        );

        // Return the created notification instance.
        return $notification;
    }
}
