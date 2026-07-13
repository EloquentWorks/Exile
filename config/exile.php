<?php

declare(strict_types=1);

use EloquentWorks\Exile\Models\Ban;
use EloquentWorks\Exile\Models\BanAppeal;
use EloquentWorks\Exile\Models\DeviceFingerprint;
use EloquentWorks\Exile\Models\Evidence;
use EloquentWorks\Exile\Models\ModerationAction;
use EloquentWorks\Exile\Models\Restriction;
use EloquentWorks\Exile\Models\Strike;
use EloquentWorks\Exile\Models\Warning;

return [
    'tables' => [
        'bans' => 'exile_bans',
        'restrictions' => 'exile_restrictions',
        'strikes' => 'exile_strikes',
        'warnings' => 'exile_warnings',
        'appeals' => 'exile_appeals',
        'evidence' => 'exile_evidence',
        'device_fingerprints' => 'exile_device_fingerprints',
        'actions' => 'exile_actions',
    ],

    'models' => [
        'ban' => Ban::class,
        'restriction' => Restriction::class,
        'strike' => Strike::class,
        'warning' => Warning::class,
        'appeal' => BanAppeal::class,
        'evidence' => Evidence::class,
        'device_fingerprint' => DeviceFingerprint::class,
        'action' => ModerationAction::class,
    ],

    'security' => [
        'hash_key' => env('EXILE_HASH_KEY', env('APP_KEY')),
        'device_header' => 'X-Device-Fingerprint',
        'trust_request_ip' => true,
    ],

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

    'responses' => [
        'ban_message' => 'Your access has been suspended.',
        'restriction_message' => 'This action is currently restricted.',
        'include_reason' => true,
        'include_expiration' => true,
    ],

    'notifications' => [
        'enabled' => false,
        'channels' => ['mail'],
        'issued' => true,
        'revoked' => true,
        'expired' => true,
        'appeals' => true,
    ],

    'appeals' => [
        'enabled' => true,
        'allow_multiple_pending' => false,
        'max_message_length' => 3000,
    ],

    'evidence' => [
        'disk' => 'local',
        'directory' => 'exile/evidence',
        'max_size_kilobytes' => 10240,
    ],

    'strikes' => [
        'default_points' => 1,
        'expire_after_days' => null,
    ],

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

    'middleware' => [
        'ban_alias' => 'exile',
        'restriction_alias' => 'exile.allowed',
        'shadow_alias' => 'exile.shadow',
    ],

    'schedule' => [
        'enabled' => true,
        'expire_frequency' => 'hourly',
        'prune_frequency' => 'daily',
    ],

    'retention' => [
        'prune_enabled' => false,
        'days' => 365,
    ],

    'audit' => [
        'enabled' => true,
    ],
];
