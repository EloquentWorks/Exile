# Middleware

Exile registers three configurable aliases.

## Ban enforcement

Default alias:

```text
exile
```

```php
Route::middleware(['auth', 'exile'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class);
});
```

The middleware checks the authenticated account and may also include the trusted request IP and configured device header.

## Restriction enforcement

Default alias:

```text
exile.allowed
```

```php
Route::post('/posts', StorePostController::class)
    ->middleware([
        'auth',
        'exile',
        'exile.allowed:posting',
    ]);
```

Supported restriction values:

```text
login
posting
read_only
shadow
```

A read-only restriction also blocks posting.

## Shadow marker

Default alias:

```text
exile.shadow
```

```php
Route::post('/comments', StoreCommentController::class)
    ->middleware([
        'auth',
        'exile.shadow',
    ]);
```

This middleware adds request attributes instead of rejecting the request.

## Ordering

A typical route stack is:

```php
Route::middleware([
    'web',
    'auth',
    'exile',
    'exile.allowed:posting',
])->group(function (): void {
    // Protected routes...
});
```

Authentication normally runs first so the account is available to Exile.

## Error responses

Applications may customize the rendering of package exceptions through Laravel's exception configuration.

Do not expose internal notes, evidence metadata, staff identity, or private detection rules in blocked responses.
