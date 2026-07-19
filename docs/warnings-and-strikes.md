# ⚠️ Warnings, Strikes, and Escalation

Warnings communicate moderation decisions. Strikes add points that can trigger automatic enforcement.

## ⚠️ Warnings

```php
use EloquentWorks\Exile\Enums\WarningSeverity;

$warning = $user->warn(
    reason: 'Please review the community rules.',
    severity: WarningSeverity::High,
    category: 'spam',
    internalNotes: 'Second warning this month.',
    moderator: $moderator,
);
```

A warning may be acknowledged:

```php
$warning->acknowledge();
```

## ⚠️ Strikes

```php
$strike = $user->strike(
    reason: 'Repeated spam',
    points: 3,
    category: 'spam',
    expiresAt: now()->addMonths(6),
    moderator: $moderator,
);
```

When points are omitted, Exile uses `strikes.default_points`.

When expiration is omitted and `strikes.expire_after_days` is a positive integer, Exile supplies the configured default expiration.

## 📊 Active points

```php
$points = $user->activeStrikePoints();
```

Only active, non-revoked, non-expired strikes count.

## ⚙️ Escalation configuration

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
            'points' => 10,
            'action' => 'ban',
            'type' => 'account',
            'duration' => 'P30D',
            'reason' => 'Automatic account ban.',
        ],
    ],
],
```

## 🔐 Concurrency safety

Escalation evaluation:

1. starts a database transaction
2. locks the affected account row
3. calculates active points
4. sorts thresholds from highest to lowest
5. reserves the threshold with `insertOrIgnore()`
6. applies one action
7. records an audit entry

The `exile_escalations` table contains a unique index on:

```text
escalatable_type
escalatable_id
threshold_points
```

Concurrent requests therefore cannot reserve the same threshold twice.

## ♻️ Revoke a strike

```php
Exile::revokeStrike(
    $strike,
    $moderator
);
```
