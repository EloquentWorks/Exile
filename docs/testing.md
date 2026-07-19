# Testing

## Quality suite

```bash
composer quality
```

Equivalent commands:

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/phpunit
```

## Required release coverage

### Enforcement transactions

Test that:

- audit failure rolls back enforcement
- successful writes create both records
- events are not dispatched after rollback
- after-commit callbacks run after commit

### Combined-ban behavior

Test both modes:

```php
config()->set(
    'exile.security.combined_ban_match',
    'any'
);
```

```php
config()->set(
    'exile.security.combined_ban_match',
    'all'
);
```

Cover every correct/incorrect account, IP, and device combination.

### Evidence integrity

Test that:

- uploaded evidence receives a 64-character SHA-256 hash
- unchanged files validate
- modified files fail validation
- unreadable files fail validation
- failed checksum creation removes the newly stored file

### Escalation concurrency

Test that:

- the highest newly reached threshold is selected
- repeated evaluation does not duplicate enforcement
- the applied-escalation record is unique
- account locking occurs within a transaction
- disabled escalation performs no action

### Notifications

Test that:

- notifications remain disabled by default
- issued/revoked/expired toggles are honored
- configured classes are resolved
- invalid classes fail according to `fail_silently`
- templates receive expected variables
- published views override package views
- queued notifications use after-commit behavior

### Installation

Test:

```bash
php artisan exile:install
php artisan exile:install --migrate
php artisan exile:install --views
```

## Compatibility matrix

The package's Composer constraints currently support:

- Laravel 11.15+
- Laravel 12
- Laravel 13

CI should test all advertised Laravel versions before release.

Recommended minimum matrix:

```yaml
- php: '8.2'
  illuminate: '^11.15'
  testbench: '^9.0'
  phpunit: '^11.5'

- php: '8.2'
  illuminate: '^12.0'
  testbench: '^10.0'
  phpunit: '^11.5'

- php: '8.3'
  illuminate: '^13.0'
  testbench: '^11.0'
  phpunit: '^12.0'
```

## Clean-application smoke tests

Test tagged code in clean Laravel 11, 12, and 13 applications:

1. Composer installation
2. package discovery
3. installation command
4. migrations
5. trait usage
6. one account ban
7. middleware blocking
8. one queued notification
9. evidence checksum
10. escalation threshold
