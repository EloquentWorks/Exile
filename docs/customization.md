# Customization

## Custom models

Extend the package model:

```php
<?php

namespace App\Models;

use EloquentWorks\Exile\Models\Ban as BaseBan;

class Ban extends BaseBan
{
    // Application-specific relationships or helpers.
}
```

Register it:

```php
'models' => [
    'ban' => App\Models\Ban::class,
],
```

Repeat the pattern for restrictions, strikes, warnings, appeals, evidence, device fingerprints, and moderation actions.

## Custom table names

```php
'tables' => [
    'bans' => 'moderation_bans',
    // ...
],
```

Set names before publishing and running migrations.

## Custom categories

```php
'categories' => [
    'spam',
    'harassment',
    'fraud',
    'cheating',
    'ban_evasion',
    'chargeback',
    'other',
],
```

The writer validates configured categories.

## Custom middleware aliases

```php
'middleware' => [
    'ban_alias' => 'moderation.not-banned',
    'restriction_alias' => 'moderation.allowed',
    'shadow_alias' => 'moderation.shadow',
],
```

Use the new aliases in routes.

## Dependency injection

Instead of the facade:

```php
use EloquentWorks\Exile\Services\ExileManager;

final class BanUser
{
    public function __construct(
        private ExileManager $exile
    ) {}

    public function handle(User $user): void
    {
        $this->exile->banAccount(
            account: $user,
            reason: 'Policy violation',
        );
    }
}
```

## Custom notifications

Listen to events and send application-specific notifications:

```php
Event::listen(
    AppealSubmitted::class,
    NotifyModerationTeam::class
);
```

## Custom authorization

Exile does not register admin controllers or policies. Use application policies for:

- issuing enforcement
- viewing internal notes
- reviewing appeals
- downloading evidence
- revoking enforcement
- browsing audit history

## Custom metadata

Every major enforcement API accepts metadata:

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

Use metadata for non-core attributes rather than adding columns for every integration-specific value.
