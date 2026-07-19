# Release Checklist

This checklist prepares Laravel Exile `v1.1.0`.

## Release blockers

### Align README, Composer, and CI

`composer.json` supports Laravel 11.15, 12, and 13. The current GitHub Actions matrix tests Laravel 12 and 13 only.

Before release, either:

- add Laravel 11 to CI, or
- remove Laravel 11 from Composer support and documentation

Adding the missing CI job is recommended.

### Correct the appeal action configuration

The mail templates read:

```php
exile.notifications.mail.issued.action_text
exile.notifications.mail.issued.action_url
```

Remove any top-level `action_text` and `action_url` keys. Put the values inside `notifications.mail.issued`.

Do not ship an `example.com` action URL as an active default. Use `null` unless the consuming application configures a real route.

### Test the new public behavior

Add or confirm tests for:

- transactional enforcement and rollback
- after-commit event dispatch
- queued notification classes
- custom Markdown views
- notification failure handling
- combined-ban `any` and `all`
- evidence checksums
- escalation deduplication and locking
- installation with `--views`
- new migrations

### Confirm migration behavior

Verify clean installation runs every migration exactly once.

The provider currently loads package migrations and also makes them publishable. Confirm the chosen installation strategy in clean applications and avoid duplicate migration execution.

## Documentation

Confirm:

- README matches Composer constraints
- every docs link resolves
- examples match current method signatures
- queue requirements are documented
- the checksum migration is documented
- the escalation migration is documented
- combined matching is documented
- no placeholder production URL remains
- `RELEASE.md` references Exile and `v1.1.0`

## Quality

```bash
composer validate --strict
composer quality
```

Also run:

```bash
composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader
```

## Compatibility

Smoke-test clean applications for:

- Laravel 11.15 / PHP 8.2
- Laravel 12 / PHP 8.2
- Laravel 13 / PHP 8.3

## Database checks

At minimum, run SQLite. Before a broader production claim, also test MySQL and PostgreSQL for:

- morph indexes
- JSON metadata
- transactions
- `lockForUpdate()`
- `insertOrIgnore()`
- unique escalation reservations
- migrations and rollbacks

## Security review

Confirm:

- no secrets are committed
- default evidence disk guidance is safe
- `EXILE_HASH_KEY` is documented
- internal notes are never included in public notifications
- placeholder appeal URLs are disabled
- evidence downloads require authorization
- pruning remains opt-in

## Tag

After every check passes:

```bash
git add .
git commit -m "Prepare v1.1.0 release"
git push
```

```bash
git tag -a v1.1.0 -m "Laravel Exile v1.1.0"
git push origin v1.1.0
```

Create a GitHub Release using `RELEASE_NOTES_v1.1.0.md`.

## Post-release

From a clean application:

```bash
composer require eloquent-works/exile:^1.1
php artisan exile:install --migrate --views
```

Verify the resolved version, migrations, middleware, queued notification, checksum, and escalation behavior.
