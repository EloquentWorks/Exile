# Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=exile-config
```

The published file is located at `config/exile.php`.

## Tables

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
],
```

Change table names before publishing and running migrations. Changing them afterward requires an application migration.

## Models

```php
'models' => [
    'ban' => EloquentWorks\Exile\Models\Ban::class,
    'restriction' => EloquentWorks\Exile\Models\Restriction::class,
    'strike' => EloquentWorks\Exile\Models\Strike::class,
    'warning' => EloquentWorks\Exile\Models\Warning::class,
    'appeal' => EloquentWorks\Exile\Models\BanAppeal::class,
    'evidence' => EloquentWorks\Exile\Models\Evidence::class,
    'device_fingerprint' => EloquentWorks\Exile\Models\DeviceFingerprint::class,
    'action' => EloquentWorks\Exile\Models\ModerationAction::class,
],
```

Replacement models should extend the corresponding Exile model.

## Security

```php
'security' => [
    'hash_key' => env('EXILE_HASH_KEY', env('APP_KEY')),
    'device_header' => 'X-Device-Fingerprint',
    'trust_request_ip' => true,
],
```

- `hash_key` signs deterministic hashes used for IP and device matching.
- `device_header` identifies the request header read by middleware.
- `trust_request_ip` controls whether middleware includes the request IP in its enforcement context.

Use a dedicated `EXILE_HASH_KEY` in production. Configure Laravel trusted proxies correctly before trusting `$request->ip()` behind a load balancer or reverse proxy.

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

When the list is not empty, Exile rejects ban, strike, or warning categories that are not configured. Pass `null` when no category is needed.

## Response messages

```php
'responses' => [
    'ban_message' => 'Your access has been suspended.',
    'restriction_message' => 'This action is currently restricted.',
    'include_reason' => true,
    'include_expiration' => true,
],
```

Consider disabling reason output when internal moderation reasons may expose sensitive details.

## Notifications

```php
'notifications' => [
    'enabled' => false,
    'channels' => ['mail'],
    'issued' => true,
    'revoked' => true,
    'expired' => true,
],
```

The bundled notification dispatcher currently sends notifications for ban issuance, revocation, and expiration. Appeal workflows dispatch events but do not currently include bundled appeal notification classes.

The affected model must support Laravel notifications.

## Appeals

```php
'appeals' => [
    'enabled' => true,
    'allow_multiple_pending' => false,
    'max_message_length' => 3000,
],
```

The manager trims and validates appeal messages and can prevent multiple pending appeals for the same ban.

## Evidence

```php
'evidence' => [
    'disk' => 'local',
    'directory' => 'exile/evidence',
    'max_size_kilobytes' => 10240,
],
```

Exile checks the configured size limit when `storeEvidence()` is used. Your application should still validate MIME types, extensions, and authorization.

## Strike defaults

```php
'strikes' => [
    'default_points' => 1,
],
```

`default_points` is used when no explicit point value is passed.

> If your config still contains `expire_after_days`, either implement it before release or remove it. The current strike writer only uses an explicitly supplied `expiresAt` value.

## Automatic escalation

```php
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
    ],
],
```

- `action` may be `ban` or `restriction`.
- `type` must match the corresponding enum value.
- `duration` uses an ISO 8601 interval such as `P1D`, `P7D`, or `P30D`.
- An empty or invalid duration produces a permanent action.
- The engine selects the highest qualifying unapplied threshold and applies one escalation action per evaluation.

## Middleware aliases

```php
'middleware' => [
    'ban_alias' => 'exile',
    'restriction_alias' => 'exile.allowed',
    'shadow_alias' => 'exile.shadow',
],
```

Changing aliases changes the names used in application routes.

## Scheduling

```php
'schedule' => [
    'enabled' => true,
    'expire_frequency' => 'hourly',
    'prune_frequency' => 'daily',
],
```

Supported values are:

- `every_fifteen_minutes`
- `every_thirty_minutes`
- `hourly`
- `daily`
- `weekly`

Unknown values fall back to hourly.

## Retention

```php
'retention' => [
    'prune_enabled' => false,
    'days' => 365,
],
```

Pruning is intentionally disabled by default because moderation history may be legally, operationally, or administratively important.

## Audit logging

```php
'audit' => [
    'enabled' => true,
],
```

When enabled, moderation actions are stored in the configured actions table.
