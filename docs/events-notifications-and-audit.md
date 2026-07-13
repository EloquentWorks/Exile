# Events, Notifications, and Audit History

## Events

Exile dispatches these lifecycle events:

- `BanIssued`
- `BanRevoked`
- `BanExpired`
- `RestrictionIssued`
- `RestrictionRevoked`
- `StrikeIssued`
- `WarningIssued`
- `AppealSubmitted`
- `AppealResolved`

Register listeners in the consuming application:

```php
use EloquentWorks\Exile\Events\BanIssued;
use Illuminate\Support\Facades\Event;

Event::listen(
    BanIssued::class,
    SendModerationWebhook::class
);
```

Events are useful for:

- application notifications
- webhooks
- analytics
- staff alerts
- external case systems
- activity feeds

## Bundled notifications

The current package includes:

- `BanIssuedNotification`
- `BanRevokedNotification`
- `BanExpiredNotification`

Enable notifications:

```php
'notifications' => [
    'enabled' => true,
    'channels' => ['mail'],
    'issued' => true,
    'revoked' => true,
    'expired' => true,
],
```

The affected model must support Laravel notifications.

Appeal events are included, but appeal notification classes are not currently bundled.

## Audit history

When enabled:

```php
'audit' => [
    'enabled' => true,
],
```

Exile records important activity in `exile_actions`.

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

## Query audit records

```php
use EloquentWorks\Exile\Models\ModerationAction;

$actions = ModerationAction::query()
    ->latest()
    ->paginate();
```

Filter by subject:

```php
$actions = ModerationAction::query()
    ->where('subject_type', $ban->getMorphClass())
    ->where('subject_id', $ban->getKey())
    ->latest()
    ->get();
```

## Audit context

The `context` attribute stores structured metadata such as:

- ban type
- category
- account type and ID
- strike points
- escalation threshold
- evidence ID

Applications may add their own metadata to enforcement records for case references, source systems, or correlation IDs.

## Privacy

Audit data may contain sensitive identifiers and staff activity. Apply authorization, retention, and access logging to moderation dashboards.
