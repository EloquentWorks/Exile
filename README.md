[![tests](https://github.com/EloquentWorks/Exile/actions/workflows/tests.yml/badge.svg)](https://github.com/EloquentWorks/Exile/actions/workflows/tests.yml)

# Laravel Exile

Comprehensive moderation enforcement tools for Laravel applications.

Exile supports account, IP, CIDR network, and device bans; temporary and permanent enforcement; warnings and strike escalation; login, posting, read-only, and shadow restrictions; appeals, evidence, moderator tracking, audit history, events, notifications, middleware, and scheduled maintenance.

```php
$user->ban(
    reason: 'Repeated harassment',
    expiresAt: now()->addDays(7),
    moderator: $moderator,
);

$user->strike('Spam', points: 3);

$user->restrict(RestrictionType::Posting, 'Posting cooldown');
```

## Features

- Account-only bans
- IP-only bans
- Combined account and IP bans
- CIDR network bans with IPv4 and IPv6 support
- Device fingerprint bans without storing raw fingerprints
- Combined account, device, and IP bans
- Temporary and permanent enforcement
- Public reasons and private moderator notes
- Configurable ban categories
- Moderator issuance and revocation tracking
- Warnings with severity and acknowledgement
- Strike points with automatic escalation
- Login, posting, read-only, and shadow restrictions
- Ban appeals with approval, denial, and withdrawal
- Evidence attachments with configurable storage
- Automatic audit history
- Middleware and custom 403 responses
- Lifecycle events and optional notifications
- Expiration processing and retention pruning commands
- Configurable models and table names

## Requirements

- PHP 8.2+ for Laravel 11 or 12
- PHP 8.3+ for Laravel 13
- Laravel 11, 12, or 13
- An Eloquent user model

## Installation

```bash
composer require eloquent-works/exile
php artisan exile:install --migrate
```

Add `Bannable` to your user model:

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

## Account bans

```php
$ban = $user->ban(
    reason: 'Repeated harassment',
    expiresAt: now()->addDays(7),
    moderator: $moderator,
    category: 'harassment',
    internalNotes: 'Case EX-1042',
    metadata: ['case_number' => 'EX-1042'],
);

$user->isBanned();
```

## IP, network, and device bans

```php
use EloquentWorks\Exile\Facades\Exile;

Exile::banIp('203.0.113.10', reason: 'Automated abuse');

Exile::banNetwork('203.0.113.0/24', reason: 'Abusive network');

Exile::banDevice('browser-fingerprint', reason: 'Ban evasion');

Exile::banAccountAndIp(
    $user,
    request()->ip(),
    reason: 'Ban evasion',
);
```

Register device activity without storing the raw fingerprint:

```php
$user->registerDeviceFingerprint(
    fingerprint: $request->header('X-Device-Fingerprint'),
    ipAddress: $request->ip(),
    label: 'Chrome on Windows',
);
```

## Warnings and strikes

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

Escalation thresholds are configured in `config/exile.php`. The defaults apply:

- 3 points: one-day posting restriction
- 5 points: seven-day read-only restriction
- 10 points: thirty-day account ban

## Restrictions

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

## Middleware

Protect all access against account, IP, network, and device bans:

```php
Route::middleware('exile')->group(function () {
    Route::get('/dashboard', DashboardController::class);
});
```

Protect specific actions:

```php
Route::post('/posts', StorePostController::class)
    ->middleware('exile.allowed:posting');

Route::post('/login/complete', CompleteLoginController::class)
    ->middleware('exile.allowed:login');
```

Mark shadow-banned requests without rejecting them:

```php
Route::post('/comments', StoreCommentController::class)
    ->middleware('exile.shadow');

$shadowed = request()->attributes->get('exile.shadowed', false);
```

## Appeals

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

## Evidence

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

## Revocation

```php
Exile::revokeBan($ban, $moderator);
Exile::revokeRestriction($restriction, $moderator);
Exile::revokeStrike($strike, $moderator);
```

Records remain available as moderation history until explicitly pruned.

## Commands

```bash
php artisan exile:expire
php artisan exile:prune
```

Pruning is disabled by default. Enable it in configuration or run:

```bash
php artisan exile:prune --force --days=365
```

## Notifications

Notifications are disabled by default. Enable them in `config/exile.php`:

```php
'notifications' => [
    'enabled' => true,
    'channels' => ['mail'],
],
```

The affected model must support Laravel notifications. Database notifications also require Laravel's notifications table.

## Events

- `BanIssued`
- `BanRevoked`
- `BanExpired`
- `RestrictionIssued`
- `RestrictionRevoked`
- `StrikeIssued`
- `WarningIssued`
- `AppealSubmitted`
- `AppealResolved`

## Testing

```bash
composer test
composer analyse
vendor/bin/pint --test
```

## Security

Keep `EXILE_HASH_KEY` private. Exile uses keyed HMAC hashes for IP and device matching. Human-readable IP addresses and CIDR ranges are encrypted at rest using Laravel's encryption system.

Do not use device fingerprints as a sole identity signal. Treat them as one moderation indicator alongside account history, IP context, and human review.

## License

The MIT License.
