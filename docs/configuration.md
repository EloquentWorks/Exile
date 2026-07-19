# Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=exile-config
```

The published file is `config/exile.php`.

## Tables and models

Exile lets applications replace table names and package models:

```php
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
```

Set custom table names before publishing and running migrations.

Replacement models should extend the corresponding Exile model.

## Security

```php
'security' => [
    'hash_key' => env(
        'EXILE_HASH_KEY',
        env('APP_KEY')
    ),
    'device_header' => 'X-Device-Fingerprint',
    'trust_request_ip' => true,
    'combined_ban_match' => 'any',
],
```

### `hash_key`

Used for deterministic keyed hashes of IP addresses and device tokens. Configure a dedicated `EXILE_HASH_KEY` in production.

### `device_header`

The request header used by middleware to obtain a device token.

### `trust_request_ip`

Controls whether middleware includes `$request->ip()` in the enforcement context. Configure trusted proxies correctly before enabling this behind a proxy.

### `combined_ban_match`

Supported values:

- `any`: a combined ban matches any stored identifier.
- `all`: every identifier required by the combined type must match.

`any` preserves the original package behavior.

## Categories

```php
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
```

When categories are configured, unsupported category values are rejected.

## Response disclosure

```php
'responses' => [
    'ban_message' => 'Your access has been suspended.',
    'restriction_message' => 'This action is currently restricted.',
    'include_reason' => true,
    'include_expiration' => true,
],
```

Disable reason output when moderation reasons contain private detection details.

## Notifications

```php
'notifications' => [
    'enabled' => false,
    'channels' => ['mail'],
    'issued' => true,
    'revoked' => true,
    'expired' => true,
    'fail_silently' => true,

    'classes' => [
        'issued' => BanIssuedNotification::class,
        'revoked' => BanRevokedNotification::class,
        'expired' => BanExpiredNotification::class,
    ],

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
            'outro' => 'Contact support if you believe this was issued in error.',
            'salutation' => null,
        ],

        'date_format' => 'M j, Y g:i A T',
        'timezone' => null,
    ],
],
```

`fail_silently` reports notification construction or dispatch errors without reversing an already committed enforcement action.

Custom notification classes must extend Laravel's `Notification` class and should accept a `Ban` through a constructor parameter named `$ban`.

The action button belongs inside the applicable mail template configuration:

```php
'notifications' => [
    'mail' => [
        'issued' => [
            'action_text' => 'Appeal this enforcement',
            'action_url' => 'https://example.test/account/appeals',
        ],
    ],
],
```

## Appeals

```php
'appeals' => [
    'enabled' => true,
    'allow_multiple_pending' => false,
    'max_message_length' => 3000,
],
```

## Evidence

```php
'evidence' => [
    'disk' => 'local',
    'directory' => 'exile/evidence',
    'max_size_kilobytes' => 10240,
],
```

Use a private disk for moderation evidence. The application should also validate MIME types and authorization.

## Strikes

```php
'strikes' => [
    'default_points' => 1,
    'expire_after_days' => null,
],
```

Set `expire_after_days` to a positive integer to supply a default expiration when one is not passed explicitly.

## Escalation

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

Durations use ISO 8601 intervals. An empty or invalid duration produces permanent enforcement.

The engine checks thresholds from highest to lowest and applies at most one newly reached threshold per evaluation.

## Middleware aliases

```php
'middleware' => [
    'ban_alias' => 'exile',
    'restriction_alias' => 'exile.allowed',
    'shadow_alias' => 'exile.shadow',
],
```

## Scheduling and retention

```php
'schedule' => [
    'enabled' => true,
    'expire_frequency' => 'hourly',
    'prune_frequency' => 'daily',
],

'retention' => [
    'prune_enabled' => false,
    'days' => 365,
],
```

Review operational and legal requirements before enabling destructive pruning.

## Audit logging

```php
'audit' => [
    'enabled' => true,
],
```
