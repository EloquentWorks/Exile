# 🌐 IP, Network, and Device Enforcement

Identifier enforcement should supplement account history and human review.

## 📍 Exact IP ban

```php
use EloquentWorks\Exile\Facades\Exile;

$ban = Exile::banIp(
    '203.0.113.10',
    reason: 'Automated abuse',
    expiresAt: now()->addHours(24),
    moderator: $moderator,
);
```

## 🌐 CIDR network ban

```php
$ban = Exile::banNetwork(
    '203.0.113.0/24',
    reason: 'Abusive network range',
    expiresAt: now()->addWeek(),
    moderator: $moderator,
);
```

IPv4 and IPv6 CIDR ranges are supported.

Network bans may affect unrelated users behind a shared ISP, mobile carrier, workplace, school, proxy, or VPN. Keep ranges narrow and review them carefully.

## 💻 Device ban

```php
$ban = Exile::banDevice(
    'application-generated-device-token',
    reason: 'Ban evasion',
    moderator: $moderator,
);
```

Do not use a device token as a sole identity signal. Device signals may be reset, shared, or spoofed.

## 🔗 Combined account, device, and IP ban

```php
$ban = Exile::banAccountDeviceAndIp(
    account: $user,
    ipAddress: $request->ip(),
    deviceFingerprint: $request->header(
        'X-Device-Fingerprint'
    ),
    reason: 'Repeated ban evasion',
    moderator: $moderator,
);
```

With strict matching enabled, all three identifiers must match.

## 🖥️ Register a device observation

```php
$device = $user->registerDeviceFingerprint(
    fingerprint: $request->header(
        'X-Device-Fingerprint'
    ),
    ipAddress: $request->ip(),
    label: 'Chrome on Windows',
    metadata: [
        'source' => 'login',
    ],
);
```

Raw device tokens are not stored.

## 🧭 Trusted proxies

When IP matching is enabled behind a reverse proxy or load balancer, configure Laravel trusted proxies. Otherwise, the package may receive the proxy address instead of the client address.

Disable request-IP checks when a trustworthy client IP is unavailable:

```php
'security' => [
    'trust_request_ip' => false,
],
```

## 🔑 Hash-key rotation

Changing `EXILE_HASH_KEY` prevents existing IP and device hashes from matching new requests. Treat rotation as a data migration, not a routine configuration change.
