# Configuration

Publish the package configuration:

```bash
php artisan vendor:publish --tag=exile-config
```

## Hashing key

Set a dedicated key in production:

```dotenv
EXILE_HASH_KEY=generate-a-long-random-secret
```

Changing this key prevents existing IP and device hashes from matching new requests.

## Escalation

Thresholds are checked from highest to lowest. Only the highest newly reached threshold is applied during an evaluation.

```php
'escalation' => [
    'enabled' => true,
    'thresholds' => [
        [
            'points' => 3,
            'action' => 'restriction',
            'type' => 'posting',
            'duration' => 'P1D',
            'reason' => 'Automatic posting restriction.',
        ],
        [
            'points' => 10,
            'action' => 'ban',
            'type' => 'account',
            'duration' => 'P30D',
            'reason' => 'Automatic account ban.',
        ],
    ],
],
```

Durations use ISO 8601 date intervals. Omit the duration or use an empty value for permanent enforcement.

## Retention

Pruning is opt-in because moderation history may be important for safety, legal, and audit purposes.

```php
'retention' => [
    'prune_enabled' => false,
    'days' => 365,
],
```
