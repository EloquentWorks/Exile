# Installation

## Requirements

| Laravel | PHP | Testbench |
| --- | --- | --- |
| 11.15+ | 8.2+ | 9.x |
| 12.x | 8.2+ | 10.x |
| 13.x | 8.3+ | 11.x |

## Install through Composer

```bash
composer require eloquent-works/exile
```

## Publish configuration and migrations

```bash
php artisan exile:install
```

Run the migrations separately:

```bash
php artisan migrate
```

Or publish and migrate in one command:

```bash
php artisan exile:install --migrate
```

Use `--force` only when you intentionally want to overwrite previously published package files:

```bash
php artisan exile:install --force
```

> Before the first stable release, use one migration strategy. The recommended strategy for Exile is publish-only migrations because the installer already publishes them. See [Release checklist](release-checklist.md).

## Add `Bannable` to the account model

```php
<?php

namespace App\Models;

use EloquentWorks\Exile\Traits\Bannable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Bannable;
}
```

The trait adds relationships and helpers for bans, restrictions, warnings, strikes, and registered device fingerprints.

## Configure a dedicated hash key

Create a dedicated secret:

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Add it to `.env`:

```env
EXILE_HASH_KEY=base64:replace-with-the-generated-value
```

Do not commit the value.

## Protect routes

Block active account, IP, network, and device bans:

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'exile'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class);
});
```

Protect an action from a specific restriction:

```php
Route::post('/posts', StorePostController::class)
    ->middleware(['auth', 'exile', 'exile.allowed:posting']);
```

Mark shadow-banned requests without rejecting them:

```php
Route::post('/comments', StoreCommentController::class)
    ->middleware(['auth', 'exile.shadow']);
```

## Enable scheduled processing

Exile registers its scheduled commands when scheduling is enabled. The consuming Laravel application must still run Laravel's scheduler:

```cron
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

For local development:

```bash
php artisan schedule:work
```

Continue with [Configuration](configuration.md) and [Bans](bans.md).
