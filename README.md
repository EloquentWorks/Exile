# 🛡️ Laravel Exile

[![Tests](https://github.com/EloquentWorks/Exile/actions/workflows/tests.yml/badge.svg)](https://github.com/EloquentWorks/Exile/actions/workflows/tests.yml)
[![Latest Release](https://img.shields.io/github/v/release/EloquentWorks/Exile)](https://github.com/EloquentWorks/Exile/releases)
[![License](https://img.shields.io/github/license/EloquentWorks/Exile)](LICENSE)

Comprehensive moderation-enforcement tools for Laravel applications.

Laravel Exile supports account, IP, CIDR network, and device bans; temporary and permanent enforcement; warnings and strike escalation; login, posting, read-only, and shadow restrictions; appeals, evidence, moderator tracking, audit history, events, notifications, middleware, and scheduled maintenance.

```php
$user->ban(
    reason: 'Repeated harassment',
    expiresAt: now()->addDays(7),
    moderator: $moderator,
);

$user->strike(
    reason: 'Spam',
    points: 3,
);

$user->restrict(
    RestrictionType::Posting,
    reason: 'Posting cooldown',
);
```

## 📋 Supported Versions

| Package version | PHP | Laravel / Illuminate |
|---|---:|---:|
| Current | `^8.2` | `^12.0 || ^13.0` |

> Composer automatically resolves compatible Laravel and Illuminate versions for the consuming application.

## 🚀 Installation

```bash
composer require eloquent-works/exile
php artisan exile:install --migrate
```

Publish the customizable notification templates during installation:

```bash
php artisan exile:install --migrate --views
```

Add the `Bannable` trait to the account model:

```php
<?php

namespace App\Models;

use EloquentWorks\Exile\Traits\Bannable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Bannable;
}
```

Configure a dedicated hash key:

```dotenv
EXILE_HASH_KEY=base64:replace-with-a-dedicated-random-key
```

Do not rotate this key casually. Existing IP and device hashes will no longer match after rotation.

## ✨ Features

- Account, exact-IP, CIDR network, device, and combined bans
- Configurable `any` or strict `all` matching for combined bans
- Temporary and permanent enforcement
- Transactional enforcement writes and after-commit side effects
- Login, posting, read-only, and shadow restrictions
- Warnings, strike points, and automatic escalation
- Appeals with approval, denial, withdrawal, and automatic revocation
- Evidence uploads with SHA-256 integrity checksums
- Moderator attribution, internal notes, metadata, and audit history
- Queued ban notifications with replaceable notification classes
- Publishable Markdown mail templates
- Middleware, expiration processing, and retention pruning
- Configurable models, table names, categories, and aliases

## 🛡️ Protect Routes

Block active account, IP, network, and device bans:

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'exile'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class);
});
```

Block a restricted action:

```php
Route::post('/posts', StorePostController::class)
    ->middleware(['auth', 'exile', 'exile.allowed:posting']);
```

Mark shadow-restricted requests without rejecting them:

```php
Route::post('/comments', StoreCommentController::class)
    ->middleware(['auth', 'exile.shadow']);

$shadowed = (bool) request()
    ->attributes
    ->get('exile.shadowed', false);
```

## 👤 Account Bans

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

$user->isBanned();
```

## 🌐 IP, Network, and Device Bans

```php
use EloquentWorks\Exile\Facades\Exile;

Exile::banIp(
    '203.0.113.10',
    reason: 'Automated abuse',
);

Exile::banNetwork(
    '203.0.113.0/24',
    reason: 'Abusive network',
);

Exile::banDevice(
    'application-device-token',
    reason: 'Ban evasion',
);

Exile::banAccountAndIp(
    account: $user,
    ipAddress: request()->ip(),
    reason: 'Ban evasion',
);
```

Register a device observation without storing the raw token:

```php
$user->registerDeviceFingerprint(
    fingerprint: $request->header('X-Device-Fingerprint'),
    ipAddress: $request->ip(),
    label: 'Chrome on Windows',
);
```

## ⚠️ Warnings and Strikes

```php
use EloquentWorks\Exile\Enums\WarningSeverity;

$user->warn(
    reason: 'Please review the community rules.',
    severity: WarningSeverity::High,
);

$user->strike(
    reason: 'Spam',
    points: 3,
    category: 'spam',
);

$user->activeStrikePoints();
```

Escalation thresholds are configured in `config/exile.php`.

## 🚧 Restrictions

```php
use EloquentWorks\Exile\Enums\RestrictionType;

$user->restrict(
    RestrictionType::Posting,
    reason: 'Posting cooldown',
    expiresAt: now()->addDay(),
);

$user->restrict(RestrictionType::ReadOnly);
$user->restrict(RestrictionType::Login);
$user->restrict(RestrictionType::Shadow);

$user->isRestricted(RestrictionType::Posting);
$user->isShadowBanned();
```

## ⚖️ Appeals

```php
use EloquentWorks\Exile\Enums\AppealStatus;
use EloquentWorks\Exile\Facades\Exile;

$appeal = Exile::submitAppeal(
    $ban,
    $user,
    'I believe this enforcement was issued in error.',
);

Exile::resolveAppeal(
    $appeal,
    AppealStatus::Approved,
    $reviewer,
    'Appeal accepted.',
);
```

Approving an appeal revokes the related ban.

## 📎 Evidence

Attach an existing stored file:

```php
$evidence = Exile::attachEvidence(
    subject: $ban,
    disk: 'private',
    path: 'moderation/case-1042/report.pdf',
    originalName: 'report.pdf',
    uploadedBy: $moderator,
);
```

Or store an uploaded file through Exile:

```php
$evidence = Exile::storeEvidence(
    $ban,
    $request->file('evidence'),
    $moderator,
);
```

## ♻️ Revocation

```php
Exile::revokeBan($ban, $moderator);
Exile::revokeRestriction($restriction, $moderator);
Exile::revokeStrike($strike, $moderator);
```

Records remain available as moderation history until explicitly pruned.

## ⏱️ Commands

```bash
php artisan exile:expire
php artisan exile:prune
```

Pruning is disabled by default. Enable it in configuration or explicitly force a retention period:

```bash
php artisan exile:prune --force --days=365
```

## 🔔 Notifications

Notifications are disabled by default. Enable them in `config/exile.php`:

```php
'notifications' => [
    'enabled' => true,
    'channels' => ['mail'],
],
```

The affected model must support Laravel notifications. Database notifications also require Laravel's notifications table.

## 📣 Events

- `BanIssued`
- `BanRevoked`
- `BanExpired`
- `RestrictionIssued`
- `RestrictionRevoked`
- `StrikeIssued`
- `WarningIssued`
- `AppealSubmitted`
- `AppealResolved`

## ✅ Quality Checks

Run the complete quality pipeline:

```bash
composer quality
```

Or run each check separately:

```bash
composer format
composer analyse
composer test
```

Validate Composer metadata before publishing:

```bash
composer validate --strict
```

`composer analyse` must complete with zero PHPStan errors. Do not disable valid findings globally merely to make the command pass. Prefer correcting native types, generic PHPDoc annotations, impossible comparisons, redundant `instanceof` conditions, and iterable value types.

See [Testing and Quality](docs/testing.md) and [PHPStan Remediation](docs/phpstan-remediation.md).

## 📚 Documentation

Full documentation is available in the [`docs`](docs) directory:

- [Documentation index](docs/README.md)
- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Architecture](docs/architecture.md)
- [Bans](docs/bans.md)
- [IP, Network, and Device Enforcement](docs/identifiers.md)
- [Restrictions](docs/restrictions.md)
- [Warnings, Strikes, and Escalation](docs/warnings-and-strikes.md)
- [Appeals](docs/appeals.md)
- [Evidence](docs/evidence.md)
- [Middleware](docs/middleware.md)
- [Events, Notifications, and Audit History](docs/events-notifications-and-audit.md)
- [Commands and Scheduling](docs/commands-and-scheduling.md)
- [Customization](docs/customization.md)
- [Security](docs/security.md)
- [Testing and Quality](docs/testing.md)
- [PHPStan Remediation](docs/phpstan-remediation.md)
- [Release Checklist](docs/release-checklist.md)

## 🔐 Security

Keep `EXILE_HASH_KEY` private. Exile uses keyed HMAC hashes for IP and device matching. Human-readable IP addresses and CIDR ranges are encrypted at rest using Laravel's encryption system.

Do not use device fingerprints as a sole identity signal. Treat them as one moderation indicator alongside account history, IP context, and human review.

Security vulnerabilities should be reported privately according to [SECURITY.md](SECURITY.md), not through a public issue.

## 🤝 Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## 🙏 Credits

Built by Eloquent Works.

## 📄 License

Laravel Exile is open-source software licensed under the [MIT License](LICENSE).
