# 📨 Appeals

Appeals allow an affected account to request review of a ban.

## 📤 Submit

```php
use EloquentWorks\Exile\Facades\Exile;

$appeal = Exile::submitAppeal(
    ban: $ban,
    appellant: $user,
    message: 'I believe this enforcement was issued in error.',
);
```

Exile trims and validates the message, enforces the configured maximum length, and may prevent multiple pending appeals for one ban.

## ✅ Resolve

```php
use EloquentWorks\Exile\Enums\AppealStatus;

Exile::resolveAppeal(
    appeal: $appeal,
    status: AppealStatus::Approved,
    reviewer: $moderator,
    response: 'The evidence did not support the ban.',
);
```

```php
Exile::resolveAppeal(
    appeal: $appeal,
    status: AppealStatus::Denied,
    reviewer: $moderator,
    response: 'The enforcement was upheld.',
);
```

Only pending appeals may be resolved. Approval revokes the related ban.

## ↩️ Withdraw

```php
Exile::withdrawAppeal(
    $appeal,
    $user
);
```

## 🔐 Authorization

Exile provides the workflow but does not decide who may submit, view, withdraw, approve, or deny an appeal. Implement Laravel policies or gates in the consuming application.

## ✉️ Notifications

Appeal lifecycle events are available. The package currently bundles ban-issued, ban-revoked, and ban-expired notifications—not appeal-specific notification classes.

Applications may listen to `AppealSubmitted` and `AppealResolved` and send their own notifications.
