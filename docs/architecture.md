# 🏗️ Architecture

Exile separates persistence, enforcement resolution, request middleware, side effects, and maintenance.

## 🧩 Main components

### `Bannable`

The account-facing trait provides:

```php
$user->ban();
$user->banWithIp();
$user->isBanned();
$user->restrict();
$user->isRestricted();
$user->isShadowBanned();
$user->strike();
$user->warn();
$user->activeStrikePoints();
$user->registerDeviceFingerprint();
```

It also exposes polymorphic relationships for bans, restrictions, strikes, warnings, and device observations.

### `ExileManager`

Coordinates high-level workflows:

- account, IP, network, device, and combined bans
- active-ban resolution
- restrictions
- warnings and strikes
- appeals
- evidence
- device registration
- revocation and expiration

### `EnforcementWriter`

Validates and persists bans, restrictions, warnings, and strikes.

Database writes and audit records are grouped in transactions. Lifecycle events and notification dispatch are scheduled with `DB::afterCommit()`, so side effects run only after a successful commit.

### `EnforcementContext`

Represents the identifiers available during request enforcement:

- authenticated account
- trusted client IP
- configured device header

### `IdentifierHasher`

Normalizes IP addresses and creates deterministic keyed hashes for IP and device matching.

### `IpMatcher`

Normalizes and matches IPv4 and IPv6 CIDR ranges.

### `EscalationEngine`

The engine:

1. opens a transaction
2. locks the affected account row
3. calculates active strike points
4. selects the highest newly reached threshold
5. reserves the account/threshold combination
6. applies one ban or restriction
7. records an audit action

The `exile_escalations` table has a unique constraint on account type, account ID, and threshold points.

### `NotificationDispatcher`

Creates the configured notification class, dispatches it through Laravel, and optionally reports failures without propagating them.

### `BanNotification`

The shared notification base:

- implements `ShouldQueue`
- uses Laravel's `Queueable` trait
- calls `afterCommit()`
- obtains channels, subject, view, content, and date formatting from config
- renders a configurable Markdown template

## 🔄 Request lifecycle

```text
HTTP request
    ↓
Authentication
    ↓
EnsureNotBanned
    ↓
EnforcementContext
    ↓
ExileManager::resolveActiveBan()
    ↓
Optional restriction middleware
    ↓
Optional shadow marker
    ↓
Controller
```

## 💾 Enforcement write lifecycle

```text
Validate
    ↓
Begin database transaction
    ↓
Create or update enforcement record
    ↓
Create audit record
    ↓
Commit
    ↓
Dispatch lifecycle event
    ↓
Queue notification
```

## 🗃️ Models

| Model | Purpose |
| --- | --- |
| `Ban` | Account, IP, network, device, or combined enforcement |
| `Restriction` | Capability-specific enforcement |
| `Strike` | Point-based moderation record |
| `Warning` | Severity-based warning |
| `BanAppeal` | Appeal review workflow |
| `Evidence` | File metadata and SHA-256 checksum |
| `DeviceFingerprint` | Hashed device observation |
| `ModerationAction` | Audit-history entry |
| `AppliedEscalation` | Unique reservation for an applied threshold |
