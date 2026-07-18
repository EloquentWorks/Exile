<?php

namespace EloquentWorks\Exile\Services;

use EloquentWorks\Exile\Models\Ban;
use EloquentWorks\Exile\Notifications\BanExpiredNotification;
use EloquentWorks\Exile\Notifications\BanIssuedNotification;
use EloquentWorks\Exile\Notifications\BanRevokedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

/**
 * Dispatches notifications for ban events.
 */
final class NotificationDispatcher
{
    /**
     * Dispatches a notification when a ban is issued.
     *
     * @param  Ban  $ban  The ban that was issued.
     */
    public function banIssued(Ban $ban): void
    {
        // Check if notifications for issued bans are enabled in the configuration
        if (config('exile.notifications.issued', true)) {
            $this->send($ban->bannable, new BanIssuedNotification($ban));
        }
    }

    /**
     * Dispatches a notification when a ban is revoked.
     *
     * @param  Ban  $ban  The ban that was revoked.
     */
    public function banRevoked(Ban $ban): void
    {
        // Check if notifications for revoked bans are enabled in the configuration
        if (config('exile.notifications.revoked', true)) {
            $this->send($ban->bannable, new BanRevokedNotification($ban));
        }
    }

    /**
     * Dispatches a notification when a ban expires.
     *
     * @param  Ban  $ban  The ban that has expired.
     */
    public function banExpired(Ban $ban): void
    {
        // Check if notifications for expired bans are enabled in the configuration
        if (config('exile.notifications.expired', true)) {
            $this->send($ban->bannable, new BanExpiredNotification($ban));
        }
    }

    /**
     * Sends a notification to the specified recipient.
     *
     * @param  Model|null  $recipient  The recipient of the notification.
     * @param  Notification  $notification  The notification to send.
     */
    private function send(?Model $recipient, Notification $notification): void
    {
        // Check if notifications are globally enabled and if the recipient is not null
        if (
            ! config(
                'exile.notifications.enabled',
                false
            )
            || $recipient === null
        ) {
            return;
        }

        // Check if the recipient has the necessary methods to receive notifications
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

        // Attempt to send the notification and handle any exceptions that may occur
        try {
            // Use the Notification facade to send the notification to the recipient
            NotificationFacade::send(
                $recipient,
                $notification
            );
        } catch (Throwable $exception) {
            // If the configuration is set to not fail silently, rethrow the exception
            if (
                ! config(
                    'exile.notifications.fail_silently',
                    true
                )
            ) {
                throw $exception;
            }

            // If failing silently, log the exception for debugging purposes
            report($exception);
        }
    }
}
