# 🚫 Bans

## 🎯 Ban types

```php
use EloquentWorks\Exile\Enums\BanType;
```

| Type | Target |
| --- | --- |
| `Account` | Account only |
| `Ip` | Exact IP address |
| `AccountAndIp` | Account and IP combined |
| `Network` | IPv4 or IPv6 CIDR range |
| `Device` | Device token |
| `AccountDeviceAndIp` | Account, device, and IP combined |

## 👤 Account ban

```php
$ban = $user->ban(
    reason: 'Repeated harassment',
    expiresAt: now()->addDays(7),
    moderator: $moderator,
    category: 'harassment',
    internalNotes: 'Case EX-1042',
    metadata: [
        'case_number' => 'EX-1042',
    ],
);
```

Omit `expiresAt` for permanent enforcement.

## 🌐 Account and IP ban

```php
$ban = $user->banWithIp(
    ipAddress: $request->ip(),
    reason: 'Ban evasion',
    expiresAt: now()->addMonth(),
    moderator: $moderator,
);
```

## 🔗 Combined matching semantics

```php
'security' => [
    'combined_ban_match' => 'any',
],
```

### `any`

A combined ban can match through any stored identifier.

For `AccountAndIp`, either the account or IP may match.

### `all`

Every required identifier must be present and match.

For `AccountAndIp`:

| Account | IP | Match |
| --- | --- | --- |
| Correct | Correct | Yes |
| Correct | Different | No |
| Different | Correct | No |
| Different | Different | No |

For `AccountDeviceAndIp`, account, device, and IP must all match.

## 🔍 Check a ban

```php
$user->isBanned();

$user->isBanned(
    ipAddress: $request->ip(),
    deviceFingerprint: $request->header(
        'X-Device-Fingerprint'
    ),
);
```

For HTTP requests, prefer the `exile` middleware so the enforcement context is built consistently.

## 📚 Query bans

```php
use EloquentWorks\Exile\Models\Ban;

Ban::active()->get();
Ban::expired()->get();
Ban::revoked()->get();
Ban::permanent()->get();
```

## ♻️ Revoke a ban

```php
use EloquentWorks\Exile\Facades\Exile;

Exile::revokeBan(
    $ban,
    $moderator
);
```

Revocation preserves the record and moderation history.

## 🔄 Transaction behavior

Ban creation and audit persistence run in one transaction. The `BanIssued` event and optional notification are scheduled after commit.

A failed audit write therefore rolls back the ban rather than leaving a partially completed moderation action.
