# Commands and Scheduling

## Install

```bash
php artisan exile:install
```

Options:

```bash
php artisan exile:install --migrate
php artisan exile:install --views
php artisan exile:install --migrate --views
php artisan exile:install --force
```

## Process expiration

```bash
php artisan exile:expire
```

Custom chunk size:

```bash
php artisan exile:expire --chunk=1000
```

The command processes expired enforcement records and performs the package's expiration side effects.

## Prune old data

```bash
php artisan exile:prune
```

Pruning is disabled by default. Force a manual run:

```bash
php artisan exile:prune --force
```

Override the retention period:

```bash
php artisan exile:prune --force --days=730
```

Pruning is destructive. Review appeal windows, legal holds, audit policy, and evidence-preservation requirements first.

## Automatic scheduling

```php
'schedule' => [
    'enabled' => true,
    'expire_frequency' => 'hourly',
    'prune_frequency' => 'daily',
],
```

The consuming application must run Laravel's scheduler:

```cron
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

Local development:

```bash
php artisan schedule:work
```

## Queues

Queued notifications require a worker:

```bash
php artisan queue:work
```

Monitor failed jobs and retry policies according to the consuming application's operational requirements.
