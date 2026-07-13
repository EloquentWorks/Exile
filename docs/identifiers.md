# IP, Network, and Device Enforcement

Identifier bans are useful for abuse prevention, but they should supplement account enforcement rather than replace it.

## Exact IP ban

```php
use EloquentWorks\Exile\Facades\Exile;

$ban = Exile::banIp(
    '203.0.113.10',
    reason: 'Automated abuse',
    expiresAt: now()->addHours(24),
    moderator: $moderator,
    category: 'abuse',
);
```

## CIDR network ban

```php
$ban = Exile::banNetwork(
    '203.0.113.0/24',
    reason: 'Abusive network range',
    expiresAt: now()->addWeek(),
    moderator: $moderator,
);
```

IPv4 and IPv6 CIDR ranges are supported.

Network bans can affect innocent users sharing an ISP, mobile carrier, school, workplace, VPN, or proxy. Use narrow ranges and human review.

## Device ban

```php
$ban = Exile::banDevice(
    'application-generated-device-token',
    reason: 'Ban evasion',
    moderator: $moderator,
    category: 'ban_evasion',
);
```

Do not use a browser fingerprint as a sole identity signal. Device signals can change, collide, or be spoofed.

## Combined account, device, and IP ban

```php
$ban = Exile::banAccountDeviceAndIp(
    account: $user,
    ipAddress: $request->ip(),
    deviceFingerprint: $request->header('X-Device-Fingerprint'),
    reason: 'Repeated ban evasion',
    moderator: $moderator,
);
```

## Register a device observation

```php
$device = $user->registerDeviceFingerprint(
    fingerprint: $request->header('X-Device-Fingerprint'),
    ipAddress: $request->ip(),
    label: 'Chrome on Windows',
    metadata: [
        'source' => 'login',
    ],
);
```

Registration:

- stores a keyed fingerprint hash
- stores the latest IP as a hash
- records first and last seen timestamps
- updates the label and metadata when supplied
- creates an audit action when auditing is enabled

## Request header

The default header is:

```text
X-Device-Fingerprint
```

Change it in configuration:

```php
'security' => [
    'device_header' => 'X-Client-Device',
],
```

Your application is responsible for generating, storing, and sending a suitable device token.

## IP trust and proxies

When `trust_request_ip` is enabled, the ban middleware includes `$request->ip()` in enforcement checks. Configure Laravel trusted proxies correctly before relying on that value.

Disable request-IP checks when the deployment cannot provide a trustworthy client IP:

```php
'security' => [
    'trust_request_ip' => false,
],
```

## Hash-key rotation

IP and device matching uses deterministic keyed hashes. Changing `EXILE_HASH_KEY` prevents newly calculated hashes from matching existing records. Plan a migration or invalidate old identifier records before rotating the key.
