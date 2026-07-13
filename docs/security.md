# Security

Moderation systems handle sensitive identifiers, staff decisions, and evidence. Treat Exile as security-sensitive infrastructure.

## Dedicated hash key

Configure:

```env
EXILE_HASH_KEY=base64:your-dedicated-secret
```

The key is used for deterministic IP and device hashes.

Do not:

- commit it
- expose it to clients
- log it
- casually rotate it

Changing the key prevents existing identifier hashes from matching newly calculated values. Plan a data migration or invalidate affected records before rotation.

## IP addresses and networks

Human-readable IP and CIDR values are encrypted at rest where supported by the model casts, while hashes are used for matching.

IP addresses are not reliable identity by themselves. Shared networks, carrier NAT, VPNs, proxies, and dynamic addressing can produce false positives.

## Trusted proxies

Configure Laravel trusted proxies before enabling request-IP enforcement behind:

- Cloudflare
- AWS load balancers
- reverse proxies
- Kubernetes ingress
- hosting-platform proxies

If the client IP cannot be trusted, set:

```php
'security' => [
    'trust_request_ip' => false,
],
```

## Device fingerprints

Raw device fingerprints are hashed rather than stored.

Device signals may be spoofed, reset, or shared. Use them as one factor alongside:

- account history
- IP context
- user-agent and session history
- staff review
- appeal outcomes

## Evidence storage

Use a private disk. Require authorization for every download.

Also consider:

- MIME and extension allowlists
- malware scanning
- encrypted object storage
- short-lived download URLs
- access logs
- retention and legal holds

## Response disclosure

`include_reason` and `include_expiration` can reveal moderation data to blocked users. Avoid exposing internal notes, evidence, staff identity, detection rules, or fraud signals.

## Authorization

The package provides enforcement primitives, not an admin authorization system. Protect moderation actions with policies, gates, role checks, and audit logging.

## Metadata and internal notes

Treat internal notes and metadata as sensitive. Avoid secrets, credentials, unnecessary personal data, or unverified allegations.

## Pruning and retention

Pruning is destructive. Confirm:

- appeal windows
- legal retention
- incident-response requirements
- audit policy
- evidence preservation
- privacy requirements

before enabling automatic pruning.

## Reporting vulnerabilities

Security vulnerabilities should be reported privately according to the repository's `SECURITY.md`, not through a public issue.
