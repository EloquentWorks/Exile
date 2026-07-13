# Warnings, Strikes, and Escalation

Warnings communicate moderation decisions. Strikes add active points that can trigger automatic enforcement.

## Warning severities

```php
use EloquentWorks\Exile\Enums\WarningSeverity;
```

Available severities:

- `Info`
- `Low`
- `Medium`
- `High`
- `Final`

## Issue a warning

```php
$warning = $user->warn(
    reason: 'Please review the community rules.',
    severity: WarningSeverity::High,
    category: 'spam',
    internalNotes: 'Second warning this month.',
    moderator: $moderator,
    metadata: [
        'case_number' => 'EX-1200',
    ],
);
```

## Acknowledge a warning

```php
$warning->acknowledge();
```

The warning model records its acknowledgement timestamp.

## Issue a strike

```php
$strike = $user->strike(
    reason: 'Repeated spam',
    points: 3,
    category: 'spam',
    expiresAt: now()->addMonths(6),
    moderator: $moderator,
);
```

When `points` is omitted, Exile uses `exile.strikes.default_points`.

## Active point total

```php
$points = $user->activeStrikePoints();
```

Only active, non-revoked, non-expired strikes contribute to the total.

## Revoke a strike

```php
use EloquentWorks\Exile\Facades\Exile;

Exile::revokeStrike($strike, $moderator);
```

## Automatic escalation

Escalation runs after each new strike when enabled.

Default example:

```php
'escalation' => [
    'enabled' => true,
    'thresholds' => [
        [
            'points' => 3,
            'action' => 'restriction',
            'type' => 'posting',
            'duration' => 'P1D',
            'reason' => 'Automatic posting restriction.',
        ],
        [
            'points' => 5,
            'action' => 'restriction',
            'type' => 'read_only',
            'duration' => 'P7D',
            'reason' => 'Automatic read-only restriction.',
        ],
        [
            'points' => 10,
            'action' => 'ban',
            'type' => 'account',
            'duration' => 'P30D',
            'reason' => 'Automatic account ban.',
        ],
    ],
],
```

The engine:

1. calculates active points
2. sorts thresholds from highest to lowest
3. skips thresholds already applied to the account
4. applies the highest qualifying threshold
5. records `escalation.applied`
6. stops after one action

An invalid or empty duration results in a permanent enforcement action. Validate configuration during development.

## Strike expiration configuration

The current manager honors an explicit `expiresAt`. If the config contains `expire_after_days`, implement the default-expiration behavior or remove that option before the first stable release.
