# Restrictions

Restrictions block a capability without necessarily blocking all access.

## Types

```php
use EloquentWorks\Exile\Enums\RestrictionType;
```

| Type | Intended effect |
| --- | --- |
| `Login` | Block login completion |
| `Posting` | Block content creation |
| `ReadOnly` | Block write actions |
| `Shadow` | Mark requests for hidden handling |

## Issue a restriction

```php
$restriction = $user->restrict(
    type: RestrictionType::Posting,
    reason: 'Posting cooldown',
    expiresAt: now()->addDay(),
    moderator: $moderator,
    internalNotes: 'Repeated spam.',
    metadata: [
        'case_number' => 'EX-1104',
    ],
);
```

## Check restrictions

```php
$user->isRestricted(
    RestrictionType::Posting
);

$user->isShadowBanned();
```

A read-only restriction also blocks posting actions.

## Middleware

```php
Route::post('/posts', StorePostController::class)
    ->middleware([
        'auth',
        'exile.allowed:posting',
    ]);
```

```php
Route::post('/login/complete', CompleteLoginController::class)
    ->middleware([
        'auth',
        'exile.allowed:login',
    ]);
```

## Shadow handling

```php
Route::post('/comments', StoreCommentController::class)
    ->middleware([
        'auth',
        'exile.shadow',
    ]);
```

The middleware sets request attributes:

```php
$shadowed = (bool) request()
    ->attributes
    ->get('exile.shadowed', false);

$restriction = request()
    ->attributes
    ->get('exile.shadow_restriction');
```

Your application decides whether to hide, quarantine, review, or otherwise process shadowed content.

## Revoke

```php
Exile::revokeRestriction(
    $restriction,
    $moderator
);
```

Restriction creation, revocation, and audit writes are transactional.
