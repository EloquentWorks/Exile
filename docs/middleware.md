# Middleware

Exile registers three configurable middleware aliases.

## Ban enforcement

Default alias:

```text
exile
```

Usage:

```php
Route::middleware(['auth', 'exile'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class);
});
```

The middleware checks the authenticated account and may also check the trusted request IP and configured device header. A matching active ban throws `BannedException`.

## Restriction enforcement

Default alias:

```text
exile.allowed
```

Usage:

```php
Route::post('/posts', StorePostController::class)
    ->middleware([
        'auth',
        'exile',
        'exile.allowed:posting',
    ]);
```

Supported values:

```text
login
posting
read_only
shadow
```

A matching active restriction throws `RestrictedException`.

A read-only restriction also blocks posting actions.

## Shadow marking

Default alias:

```text
exile.shadow
```

Usage:

```php
Route::post('/comments', StoreCommentController::class)
    ->middleware(['auth', 'exile.shadow']);
```

The middleware adds request attributes rather than blocking:

```php
$shadowed = request()->attributes->get(
    'exile.shadowed',
    false
);

$restriction = request()->attributes->get(
    'exile.shadow_restriction'
);
```

## Middleware ordering

A common route stack is:

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

Authentication should normally run before Exile so the account is available.

## Exception rendering

Your application may customize ban and restriction responses in its exception handler or bootstrap exception configuration.

Example JSON response:

```php
use EloquentWorks\Exile\Exceptions\BannedException;
use Illuminate\Http\Request;

$exceptions->render(function (
    BannedException $exception,
    Request $request
) {
    return response()->json(
        $exception->toArray(),
        403
    );
});
```

Review the exception API in your installed package before customizing the renderer.

## Trusted proxies

When IP enforcement is enabled behind a proxy, configure Laravel trusted proxies. Otherwise, middleware may see the proxy address instead of the real client address.
