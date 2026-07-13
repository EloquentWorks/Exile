# Bans

Exile supports temporary and permanent bans across multiple identifiers.

## Ban types

```php
use EloquentWorks\Exile\Enums\BanType;
```

Available values:

| Enum | Value | Target |
| --- | --- | --- |
| `BanType::Account` | `account` | Account only |
| `BanType::Ip` | `ip` | Exact IP address |
| `BanType::AccountAndIp` | `account_and_ip` | Account and exact IP |
| `BanType::Network` | `network` | IPv4 or IPv6 CIDR range |
| `BanType::Device` | `device` | Device fingerprint |
| `BanType::AccountDeviceAndIp` | `account_device_and_ip` | Account, device, and IP |

## Account ban

```php
$ban = $user->ban(
    reason: 'Repeated harassment',
    expiresAt: now()->addDays(7),
    moderator: $moderator,
    category: 'harassment',
    internalNotes: 'Case EX-1042',
    metadata: [
        'case_number' => 'EX-1042',
        'source' => 'admin-panel',
    ],
);
```

Omit `expiresAt` for a permanent ban:

```php
$ban = $user->ban(
    reason: 'Confirmed fraud',
    moderator: $moderator,
    category: 'fraud',
);
```

## Account and IP ban

```php
$ban = $user->banWithIp(
    ipAddress: $request->ip(),
    reason: 'Ban evasion',
    expiresAt: now()->addMonth(),
    moderator: $moderator,
    category: 'ban_evasion',
);
```

## Check enforcement

```php
$user->isBanned();

$user->isBanned(
    ipAddress: $request->ip(),
    deviceFingerprint: $request->header('X-Device-Fingerprint'),
);
```

For request enforcement, prefer the `exile` middleware because it consistently builds the full context.

## Query bans

```php
use EloquentWorks\Exile\Models\Ban;

$active = Ban::active()->get();

$expired = Ban::expired()->get();

$revoked = Ban::revoked()->get();

$permanent = Ban::permanent()->get();
```

Account history:

```php
$history = $user->bans()
    ->latest()
    ->get();
```

## Ban status

```php
$ban->isActive();

$ban->isExpired();

$ban->isPermanent();

$ban->isRevoked();
```

## Revoke a ban

```php
use EloquentWorks\Exile\Facades\Exile;

Exile::revokeBan($ban, $moderator);
```

Revocation preserves the record and records the reviewer and timestamp.

## Expiration

Expired bans stop matching the `active()` scope immediately. The `exile:expire` command records expiration notification state, dispatches `BanExpired`, logs the expiration, and sends the enabled expiration notification.

## Reasons and internal notes

Use `reason` for text that may be shown to the affected user. Use `internalNotes` for staff-only context.

Your application controls access to both fields and should never expose internal notes through public resources or exception rendering.
