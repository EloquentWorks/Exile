<?php

use EloquentWorks\Exile\Models\AppliedEscalation;
use EloquentWorks\Exile\Models\Ban;
use EloquentWorks\Exile\Models\BanAppeal;
use EloquentWorks\Exile\Models\DeviceFingerprint;
use EloquentWorks\Exile\Models\Evidence;
use EloquentWorks\Exile\Models\ModerationAction;
use EloquentWorks\Exile\Models\Restriction;
use EloquentWorks\Exile\Models\Strike;
use EloquentWorks\Exile\Models\Warning;
use EloquentWorks\Exile\Notifications\BanExpiredNotification;
use EloquentWorks\Exile\Notifications\BanIssuedNotification;
use EloquentWorks\Exile\Notifications\BanRevokedNotification;

return [

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | These values define the database tables used by Exile. You may change
    | them before running the package migrations when your application uses
    | custom table names or naming conventions.
    |
    */

    'tables' => [
        'bans' => 'exile_bans',
        'restrictions' => 'exile_restrictions',
        'strikes' => 'exile_strikes',
        'warnings' => 'exile_warnings',
        'appeals' => 'exile_appeals',
        'evidence' => 'exile_evidence',
        'device_fingerprints' => 'exile_device_fingerprints',
        'actions' => 'exile_actions',
        'escalations' => 'exile_escalations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Models
    |--------------------------------------------------------------------------
    |
    | These model classes are used internally by Exile. Applications may
    | replace them with custom models that extend the corresponding package
    | model when additional relationships or behavior are required.
    |
    */

    'models' => [
        'ban' => Ban::class,
        'restriction' => Restriction::class,
        'strike' => Strike::class,
        'warning' => Warning::class,
        'appeal' => BanAppeal::class,
        'evidence' => Evidence::class,
        'device_fingerprint' => DeviceFingerprint::class,
        'action' => ModerationAction::class,
        'escalation' => AppliedEscalation::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | The hash key is used to create deterministic hashes for values such as
    | IP addresses and device fingerprints. The raw value can remain encrypted
    | while the hash is used for indexed comparisons.
    |
    | The device header defines the request header used to identify a client
    | device. Only trust request IP addresses when your application's proxy
    | and trusted-proxy configuration has been configured correctly.
    |
    | Combined-ban matching supports two modes:
    |
    | "any" preserves the original behavior and matches when any identifier
    | stored by a combined ban matches the enforcement context.
    |
    | "all" requires every identifier belonging to the combined ban type to
    | be present and match. For AccountAndIp, both the account and IP address
    | must match. For AccountDeviceAndIp, all three identifiers must match.
    |
    */

    'security' => [
        'hash_key' => env('EXILE_HASH_KEY', env('APP_KEY')),
        'device_header' => 'X-Device-Fingerprint',
        'trust_request_ip' => true,
        'combined_ban_match' => 'any',
    ],

    /*
    |--------------------------------------------------------------------------
    | Moderation Categories
    |--------------------------------------------------------------------------
    |
    | These categories may be assigned to bans, warnings, strikes, restrictions,
    | and other moderation records. Applications may add or remove categories
    | to match their own moderation policies.
    |
    */

    'categories' => [
        'spam',
        'harassment',
        'fraud',
        'cheating',
        'ban_evasion',
        'abuse',
        'security',
        'other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Enforcement Responses
    |--------------------------------------------------------------------------
    |
    | These messages are returned when Exile blocks access or prevents an
    | action. Reasons and expiration dates may be included in responses when
    | the corresponding options are enabled.
    |
    */

    'responses' => [
        'ban_message' => 'Your access has been suspended.',
        'restriction_message' => 'This action is currently restricted.',
        'include_reason' => true,
        'include_expiration' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Exile can notify affected users when account bans are issued, revoked,
    | or expired. Appeal workflows dispatch lifecycle events that applications
    | may listen to when custom notifications are required.
    |
    */

    'notifications' => [
        'enabled' => false,

        /*
        | These channels are returned by the bundled notification classes.
        | Applications may use any Laravel notification channel that has been
        | installed and configured.
        */
        'channels' => ['mail'],

        'issued' => true,
        'revoked' => true,
        'expired' => true,

        /*
        | When enabled, notification construction and delivery exceptions are
        | reported without reversing a successfully committed enforcement.
        */
        'fail_silently' => true,

        /*
        | Applications may replace any bundled notification class. Replacement
        | classes must extend Laravel's Notification class and should accept a
        | Ban instance through a constructor parameter named "ban".
        */
        'classes' => [
            'issued' => BanIssuedNotification::class,
            'revoked' => BanRevokedNotification::class,
            'expired' => BanExpiredNotification::class,
        ],

        /*
        | Mail templates may point to Exile's bundled Markdown views or to any
        | application view. Publish the bundled views with:
        |
        | php artisan vendor:publish --tag=exile-views
        |
        | Published templates are placed in:
        |
        | resources/views/vendor/exile/mail
        */
        'mail' => [
            'issued' => [
                'subject' => 'Account enforcement notice',
                'view' => 'exile::mail.ban-issued',
                'heading' => 'Your access has been suspended',
                'intro' => 'A moderation enforcement has been applied to your account.',
                'reason_label' => 'Reason',
                'expiration_label' => 'Expires',
                'permanent_text' => 'This enforcement is permanent.',
                'action_text' => null,
                'action_url' => null,
                'outro' => 'Contact the application support team if you believe this enforcement was issued in error.',
                'salutation' => null,
            ],

            'revoked' => [
                'subject' => 'Enforcement revoked',
                'view' => 'exile::mail.ban-revoked',
                'heading' => 'Your enforcement has been revoked',
                'intro' => 'The moderation enforcement applied to your account is no longer active.',
                'action_text' => null,
                'action_url' => null,
                'outro' => null,
                'salutation' => null,
            ],

            'expired' => [
                'subject' => 'Enforcement expired',
                'view' => 'exile::mail.ban-expired',
                'heading' => 'Your enforcement has expired',
                'intro' => 'The temporary moderation enforcement applied to your account has expired.',
                'action_text' => null,
                'action_url' => null,
                'outro' => null,
                'salutation' => null,
            ],

            /*
            | Used when formatting enforcement expiration timestamps inside the
            | bundled views. Set timezone to null to preserve the stored zone.
            */
            'date_format' => 'M j, Y g:i A T',
            'timezone' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ban Appeals
    |--------------------------------------------------------------------------
    |
    | These options control whether users may appeal bans and whether more than
    | one unresolved appeal may exist for the same ban. The maximum message
    | length should also be enforced by the consuming application's validation.
    |
    */

    'appeals' => [
        'enabled' => true,
        'allow_multiple_pending' => false,
        'max_message_length' => 3000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Moderation Evidence
    |--------------------------------------------------------------------------
    |
    | Evidence files are stored on the configured filesystem disk and inside
    | the selected directory. The maximum size is expressed in kilobytes and
    | should also be applied to upload validation in the consuming application.
    |
    */

    'evidence' => [
        'disk' => 'local',
        'directory' => 'exile/evidence',
        'max_size_kilobytes' => 10240,
    ],

    /*
    |--------------------------------------------------------------------------
    | Strike Defaults
    |--------------------------------------------------------------------------
    |
    | Each strike receives the configured number of points when no explicit
    | value is provided. Set expire_after_days to an integer to make strikes
    | expire automatically, or leave it null to keep them active indefinitely.
    |
    */

    'strikes' => [
        'default_points' => 1,
        'expire_after_days' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic Escalation
    |--------------------------------------------------------------------------
    |
    | Exile may automatically apply restrictions or bans when a user reaches
    | configured active-strike thresholds. Threshold durations use ISO 8601
    | duration values, such as P1D for one day and P30D for thirty days.
    |
    | Thresholds should be ordered from the lowest point value to the highest.
    |
    */

    'escalation' => [
        'enabled' => true,

        'thresholds' => [
            [
                'points' => 3,
                'action' => 'restriction',
                'type' => 'posting',
                'duration' => 'P1D',
                'reason' => 'Automatic restriction after accumulating 3 active strike points.',
            ],
            [
                'points' => 5,
                'action' => 'restriction',
                'type' => 'read_only',
                'duration' => 'P7D',
                'reason' => 'Automatic read-only restriction after accumulating 5 active strike points.',
            ],
            [
                'points' => 10,
                'action' => 'ban',
                'type' => 'account',
                'duration' => 'P30D',
                'reason' => 'Automatic account ban after accumulating 10 active strike points.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Aliases
    |--------------------------------------------------------------------------
    |
    | These aliases are registered by the package service provider and may be
    | used to enforce bans, action-specific restrictions, and shadow-ban state
    | on application routes.
    |
    */

    'middleware' => [
        'ban_alias' => 'exile',
        'restriction_alias' => 'exile.allowed',
        'shadow_alias' => 'exile.shadow',
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled Maintenance
    |--------------------------------------------------------------------------
    |
    | Exile can periodically expire temporary enforcement records and prune
    | old moderation data. Supported frequency values must correspond to the
    | scheduling methods implemented by the package service provider.
    |
    */

    'schedule' => [
        'enabled' => true,
        'expire_frequency' => 'hourly',
        'prune_frequency' => 'daily',
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Automatic pruning is disabled by default because moderation records may
    | be important for investigations and auditing. When enabled, records older
    | than the configured number of days may be removed by the prune command.
    |
    */

    'retention' => [
        'prune_enabled' => false,
        'days' => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, Exile records important moderation activity such as bans,
    | revocations, appeals, restrictions, strikes, warnings, and evidence
    | changes in the configured moderation-actions table.
    |
    */

    'audit' => [
        'enabled' => true,
    ],

];
