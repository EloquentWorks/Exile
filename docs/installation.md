# Installation

## Requirements

| Laravel | PHP | Testbench |
| --- | --- | --- |
| 11.15+ | 8.2+ | 9.x |
| 12.x | 8.2+ | 10.x |
| 13.x | 8.3+ | 11.x |

## Install with Composer

```bash
composer require eloquent-works/exile
```

## Publish package resources

Publish configuration and migrations:

```bash
php artisan exile:install
```

Publish and migrate:

```bash
php artisan exile:install --migrate
```

Publish the customizable mail templates too:

```bash
php artisan exile:install --migrate --views
```

Use `--force` only when intentionally replacing already-published files:

```bash
php artisan exile:install --force --views
```

Resources may also be published separately:

```bash
php artisan vendor:publish --tag=exile-config
php artisan vendor:publish --tag=exile-migrations
php artisan vendor:publish --tag=exile-views
```

## Add the trait

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

The trait provides relationships and convenience methods for bans, restrictions, warnings, strikes, and device observations.

## Configure a dedicated hash key

Generate a 32-byte key:

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Add it to `.env`:

```dotenv
EXILE_HASH_KEY=base64:generated-value
```

Changing the key later prevents existing IP and device hashes from matching newly calculated values.

## Protect routes

```php
Route::middleware(['auth', 'exile'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class);
});
```

```php
Route::post('/posts', StorePostController::class)
    ->middleware([
        'auth',
        'exile',
        'exile.allowed:posting',
    ]);
```

## Configure the queue

The bundled ban notifications implement `ShouldQueue`. When notifications are enabled, configure a queue connection and run a worker:

```bash
php artisan queue:work
```

For local development, `sync` may be used, but an asynchronous queue is recommended in production.

## Configure the scheduler

The consuming application must run Laravel's scheduler:

```cron
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

During local development:

```bash
php artisan schedule:work
```

Continue with [Configuration](configuration.md).
