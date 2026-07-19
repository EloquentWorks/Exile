# Events, Notifications, and Audit History

## Lifecycle events

Exile includes:

- `BanIssued`
- `BanRevoked`
- `BanExpired`
- `RestrictionIssued`
- `RestrictionRevoked`
- `StrikeIssued`
- `WarningIssued`
- `AppealSubmitted`
- `AppealResolved`

Example listener:

```php
use EloquentWorks\Exile\Events\BanIssued;
use Illuminate\Support\Facades\Event;

Event::listen(
    BanIssued::class,
    SendModerationWebhook::class
);
```

Enforcement-writer events are registered with `DB::afterCommit()`. They are not dispatched when the surrounding enforcement transaction rolls back.

## Bundled notifications

- `BanIssuedNotification`
- `BanRevokedNotification`
- `BanExpiredNotification`

The notifications extend `BanNotification`, implement `ShouldQueue`, use `Queueable`, and call `afterCommit()`.

Enable them:

```php
'notifications' => [
    'enabled' => true,
    'channels' => ['mail'],
    'issued' => true,
    'revoked' => true,
    'expired' => true,
    'fail_silently' => true,
],
```

Run a queue worker:

```bash
php artisan queue:work
```

## Custom mail templates

Publish the bundled views:

```bash
php artisan vendor:publish --tag=exile-views
```

Edit:

```text
resources/views/vendor/exile/mail/ban-issued.blade.php
resources/views/vendor/exile/mail/ban-revoked.blade.php
resources/views/vendor/exile/mail/ban-expired.blade.php
```

Or point config at any application Markdown view:

```php
'notifications' => [
    'mail' => [
        'issued' => [
            'subject' => 'Your account was suspended',
            'view' => 'mail.moderation.suspended',
            'heading' => 'Account suspended',
            'intro' => 'Your custom introductory message.',
            'action_text' => 'Open an appeal',
            'action_url' => 'https://example.test/appeals',
        ],
    ],
],
```

## Replace notification classes

```php
'notifications' => [
    'classes' => [
        'issued' => App\Notifications\BanIssued::class,
        'revoked' => App\Notifications\BanRevoked::class,
        'expired' => App\Notifications\BanExpired::class,
    ],
],
```

Replacement classes must extend Laravel's `Notification` class and should accept a `Ban` through a constructor argument named `$ban`.

This supports custom mail, database, Slack, SMS, webhook, and push channels.

## Failure behavior

With:

```php
'fail_silently' => true,
```

notification-construction and dispatch exceptions are reported without being rethrown. A valid, committed enforcement is not undone because mail delivery failed.

Set it to `false` when the application should surface notification failures immediately.

## Audit history

Enable audit logging:

```php
'audit' => [
    'enabled' => true,
],
```

Typical action names include:

```text
ban.issued
ban.revoked
ban.expired
restriction.issued
restriction.revoked
restriction.expired
strike.issued
strike.revoked
warning.issued
appeal.submitted
appeal.resolved
appeal.withdrawn
evidence.attached
evidence.deleted
device.seen
escalation.applied
```

Query actions:

```php
use EloquentWorks\Exile\Models\ModerationAction;

$actions = ModerationAction::query()
    ->latest()
    ->paginate();
```

Audit records may contain sensitive staff and moderation context. Protect them with authorization and retention controls.
