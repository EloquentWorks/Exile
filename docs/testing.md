# Testing

## Run the complete quality suite

```bash
composer quality
```

This runs:

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/phpunit
```

## Run individual tools

```bash
composer format

composer format:test

composer analyse

composer test
```

## Run one test class

```bash
vendor/bin/phpunit --filter BanTest
```

## Current core test areas

The package should cover:

- account bans and revocation
- IP, CIDR, and device matching
- expiration
- restrictions and shadow handling
- warnings
- strike totals and escalation
- appeals
- evidence storage
- middleware

## Additional release tests

Before `v1.0.0`, add coverage for:

- `exile:install` publishing
- `exile:expire`
- `exile:prune`
- audit enabled and disabled
- notifications enabled and disabled
- custom model configuration
- custom table configuration
- invalid categories
- invalid expiration dates
- invalid IP and CIDR values
- duplicate pending appeals
- evidence size limits
- force pruning and retention boundaries
- middleware with trusted and untrusted request IPs

## Compatibility matrix

The GitHub Actions matrix should include at least:

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

Additional PHP versions may be tested as desired.

## Clean-application smoke tests

Before release, install the tagged package into clean Laravel 11, 12, and 13 applications and verify:

1. Composer installation
2. package discovery
3. `exile:install --migrate`
4. trait registration
5. one account ban
6. middleware blocking
7. one temporary restriction
8. scheduler command execution
