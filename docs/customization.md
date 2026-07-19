# 🎨 Customization

## 🧬 Custom models

Extend the corresponding package model:

```php
<?php

namespace App\Models;

use EloquentWorks\Exile\Models\Ban as BaseBan;

class Ban extends BaseBan
{
    // Application relationships and helpers.
}
```

Register it:

```php
'models' => [
    'ban' => App\Models\Ban::class,
],
```

## 🗃️ Custom tables

```php
'tables' => [
    'bans' => 'moderation_bans',
],
```

Configure table names before publishing migrations.

## 🏷️ Custom categories

```php
'categories' => [
    'spam',
    'harassment',
    'fraud',
    'chargeback',
    'other',
],
```

## 🧱 Custom middleware aliases

```php
'middleware' => [
    'ban_alias' => 'moderation.not-banned',
    'restriction_alias' => 'moderation.allowed',
    'shadow_alias' => 'moderation.shadow',
],
```

## ✉️ Custom notification classes

```php
'notifications' => [
    'classes' => [
        'issued' => App\Notifications\CustomBanIssued::class,
    ],
],
```

```php
<?php

namespace App\Notifications;

use EloquentWorks\Exile\Models\Ban;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class CustomBanIssued extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ban $ban
    ) {
        $this->afterCommit();
    }

    // Define via(), toMail(), toArray(), etc.
}
```

## 🎨 Custom templates

Publish and edit:

```bash
php artisan vendor:publish --tag=exile-views
```

Or choose an application view in config:

```php
'view' => 'mail.moderation.ban-issued',
```

## 🔘 Custom action button

```php
'notifications' => [
    'mail' => [
        'issued' => [
            'action_text' => 'Appeal this enforcement',
            'action_url' => 'https://example.test/appeals',
        ],
    ],
],
```

## 🧾 Metadata

```php
$user->ban(
    reason: 'Repeated abuse',
    metadata: [
        'case_number' => 'EX-1204',
        'source' => 'moderation-dashboard',
        'rule_version' => '2026-07',
    ],
);
```

Use metadata for integration-specific values. Avoid secrets and unnecessary personal data.

## 🔐 Authorization

Exile does not provide an admin authorization policy. The consuming application controls who may:

- issue or revoke enforcement
- review appeals
- read internal notes
- download evidence
- browse audit history
- change escalation settings
