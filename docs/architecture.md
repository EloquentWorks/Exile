# Architecture

Exile separates enforcement decisions, persistence, request enforcement, and application integration.

## Main components

### `Bannable`

The trait is the account-facing API:

```php
$user->ban(...);
$user->restrict(...);
$user->strike(...);
$user->warn(...);
$user->isBanned();
```

It also provides polymorphic relationships:

```php
$user->bans();
$user->restrictions();
$user->strikes();
$user->warnings();
$user->deviceFingerprints();
```

### `ExileManager`

The manager coordinates higher-level workflows:

- account, IP, network, and device bans
- active-ban resolution
- restrictions
- warnings and strikes
- escalation
- appeals
- evidence
- device registration
- revocation and expiration

Use the manager through dependency injection or the `Exile` facade.

### `EnforcementWriter`

The writer validates and persists bans, restrictions, warnings, and strikes. It also dispatches events, records audit actions, and triggers supported notifications.

### `EnforcementContext`

Middleware builds an enforcement context from:

- the authenticated account
- the trusted request IP
- the configured device-fingerprint header

The manager uses that context to resolve a matching active ban.

### `IdentifierHasher` and `IpMatcher`

- `IdentifierHasher` normalizes and hashes IP addresses and device fingerprints.
- `IpMatcher` validates and matches IPv4 and IPv6 CIDR ranges.

### `EscalationEngine`

After a strike is issued, the engine calculates active strike points, finds the highest qualifying threshold that has not already been applied, and creates the configured restriction or ban.

### `AuditLogger`

The logger writes moderation actions to `exile_actions` when audit logging is enabled.

## Models

| Model | Purpose |
| --- | --- |
| `Ban` | Account, IP, network, device, or combined ban |
| `Restriction` | Login, posting, read-only, or shadow restriction |
| `Strike` | Point-based moderation record |
| `Warning` | Severity-based warning with acknowledgement |
| `BanAppeal` | Appeal and review workflow for a ban |
| `Evidence` | File metadata attached polymorphically to moderation records |
| `DeviceFingerprint` | Hashed device observation linked to an account |
| `ModerationAction` | Audit-history entry |

## Request lifecycle

A typical protected request flows through:

```text
HTTP request
    ↓
EnsureNotBanned middleware
    ↓
EnforcementContext
    ↓
ExileManager::resolveActiveBan()
    ↓
BannedException or next middleware
    ↓
Optional restriction / shadow middleware
    ↓
Controller
```

## Extension points

Applications may customize Exile through:

- replacement models
- configuration
- middleware aliases
- event listeners
- notification channels
- policies and authorization
- custom controllers and admin interfaces
- metadata and audit context
