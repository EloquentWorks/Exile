# Ban Appeals

Appeals let an affected account request review of a ban.

## Appeal statuses

```php
use EloquentWorks\Exile\Enums\AppealStatus;
```

- `Pending`
- `Approved`
- `Denied`
- `Withdrawn`

## Submit an appeal

```php
use EloquentWorks\Exile\Facades\Exile;

$appeal = Exile::submitAppeal(
    ban: $ban,
    appellant: $user,
    message: 'I believe this enforcement was issued in error.',
);
```

Exile:

- trims the message
- rejects empty messages
- enforces `max_message_length`
- can prevent multiple pending appeals for one ban
- dispatches `AppealSubmitted`
- records `appeal.submitted`

## Resolve an appeal

Approve:

```php
Exile::resolveAppeal(
    appeal: $appeal,
    status: AppealStatus::Approved,
    reviewer: $moderator,
    response: 'The evidence did not support the ban.',
);
```

Deny:

```php
Exile::resolveAppeal(
    appeal: $appeal,
    status: AppealStatus::Denied,
    reviewer: $moderator,
    response: 'The enforcement was upheld.',
);
```

Only pending appeals can be resolved. Reviewers may only choose approved or denied.

Approving an appeal automatically revokes the related ban.

## Withdraw an appeal

```php
Exile::withdrawAppeal(
    $appeal,
    $user
);
```

Only pending appeals can be withdrawn.

## Relationships

```php
$ban->appeals;

$appeal->ban;

$appeal->appellant;

$appeal->reviewedBy;
```

## Authorization

Exile provides the workflow but does not decide who may:

- submit an appeal for a ban
- read an appeal
- approve or deny an appeal
- withdraw an appeal

Use Laravel policies or gates in the consuming application.

## Notifications

Appeal submission and resolution events are included. The current bundled notification dispatcher does not include appeal notification classes; applications may listen to the events and send their own notifications.
