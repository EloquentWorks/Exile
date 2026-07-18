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
    */

    'security' => [
        'hash_key' => env('EXILE_HASH_KEY', env('APP_KEY')),
        'device_header' => 'X-Device-Fingerprint',
        'trust_request_ip' => true,

        /*
        | Supported values: any, all
        |
        | any: A combined ban matches when any stored identifier matches.
        | all: Every identifier required by the ban type must match.
        */
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
    | Exile can notify affected users when account bans are issued,
    | revoked, or expired. Appeal workflows dispatch lifecycle events that
    | applications may listen to when custom notifications are required.
    |
    */

    'notifications' => [
        'enabled' => false,
        'channels' => ['mail'],
        'issued' => true,
        'revoked' => true,
        'expired' => true,
        'fail_silently' => true,
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
