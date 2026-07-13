# Restrictions and Shadow Bans

Restrictions limit specific actions without necessarily blocking the entire account.

## Restriction types

```php
use EloquentWorks\Exile\Enums\RestrictionType;
```

| Enum | Value | Intended effect |
| --- | --- | --- |
| `RestrictionType::Login` | `login` | Block login completion |
| `RestrictionType::Posting` | `posting` | Block content creation |
| `RestrictionType::ReadOnly` | `read_only` | Block write actions |
| `RestrictionType::Shadow` | `shadow` | Mark content for hidden handling |

## Issue a restriction

```php
$restriction = $user->restrict(
    type: RestrictionType::Posting,
    reason: 'Posting cooldown',
    expiresAt: now()->addDay(),
    moderator: $moderator,
    internalNotes: 'Escalated after repeated spam.',
    metadata: [
        'case_number' => 'EX-1104',
    ],
);
```

Omit `expiresAt` for a permanent restriction.

## Check a restriction

```php
$user->isRestricted(RestrictionType::Posting);

$user->isShadowBanned();
```

A read-only restriction also satisfies a posting-restriction check.

## Protect actions

```php
Route::post('/posts', StorePostController::class)
    ->middleware('exile.allowed:posting');

Route::post('/login/complete', CompleteLoginController::class)
    ->middleware('exile.allowed:login');
```

Unknown restriction names cause an `InvalidArgumentException`, which helps catch route configuration mistakes.

## Shadow restrictions

```php
Route::post('/comments', StoreCommentController::class)
    ->middleware('exile.shadow');
```

The middleware does not reject the request. It adds:

```php
$shadowed = (bool) request()->attributes->get(
    'exile.shadowed',
    false
);

$restriction = request()->attributes->get(
    'exile.shadow_restriction'
);
```

Your controller, job, or service decides how to handle shadowed content.

## Query restrictions

```php
use EloquentWorks\Exile\Models\Restriction;

$active = Restriction::active()->get();

$posting = Restriction::ofType(
    RestrictionType::Posting
)->get();
```

## Revoke a restriction

```php
use EloquentWorks\Exile\Facades\Exile;

Exile::revokeRestriction(
    $restriction,
    $moderator
);
```

The restriction remains available in moderation history.
