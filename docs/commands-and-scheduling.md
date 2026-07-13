# Commands and Scheduling

## Installation

```bash
php artisan exile:install
```

Options:

```bash
php artisan exile:install --migrate

php artisan exile:install --force
```

The installer publishes configuration and migrations. `--migrate` runs the application's migrations afterward.

## Process expirations

```bash
php artisan exile:expire
```

Use a custom chunk size:

```bash
php artisan exile:expire --chunk=1000
```

The command processes:

- expired bans that have not emitted expiration handling
- expired restrictions that have not been marked as processed

For bans, it dispatches the expiration event, writes the audit record, and sends the enabled notification.

## Prune old data

```bash
php artisan exile:prune
```

Pruning is disabled by default. Enable it in configuration or force a manual run:

```bash
php artisan exile:prune --force
```

Override the retention period:

```bash
php artisan exile:prune --force --days=730
```

Pruning may remove old:

- expired or revoked bans
- expired or revoked restrictions
- expired or revoked strikes
- acknowledged warnings
- resolved appeals
- stale device fingerprints
- moderation actions
- evidence belonging to pruned bans

Review legal, operational, and audit requirements before enabling it.

## Automatic scheduling

```php
'schedule' => [
    'enabled' => true,
    'expire_frequency' => 'hourly',
    'prune_frequency' => 'daily',
],

'retention' => [
    'prune_enabled' => false,
    'days' => 365,
],
```

Supported frequency names:

```text
every_fifteen_minutes
every_thirty_minutes
hourly
daily
weekly
```

## Run Laravel's scheduler

Production cron:

```cron
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

Local worker:

```bash
php artisan schedule:work
```

## Deployment recommendation

Run database migrations before enabling middleware on production routes. Confirm the scheduler and queues are healthy before enabling notifications or automated pruning.
