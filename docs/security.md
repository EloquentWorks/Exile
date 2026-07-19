# 🛡️ Security

Exile handles sensitive identifiers, evidence, and staff actions.

## 🔑 Hash key

```dotenv
EXILE_HASH_KEY=base64:dedicated-secret
```

The key is used for deterministic IP and device hashes.

Do not:

- commit it
- send it to clients
- write it to logs
- rotate it without a migration plan

## 🌐 IP addresses

IP addresses are not reliable identity by themselves. Shared networks, carrier NAT, dynamic addressing, VPNs, and proxies can produce false positives.

Configure trusted proxies before enabling IP enforcement behind infrastructure that forwards client addresses.

## 🔗 Combined bans

Use `all` when a combined ban should require every stored identifier:

```php
'combined_ban_match' => 'all',
```

Use `any` only when independent matching across combined identifiers is intentional.

## 💻 Device tokens

Raw device tokens are hashed rather than stored. They may still be spoofed, reset, or shared.

Use device enforcement alongside account history, IP context, staff review, and appeals.

## 🔐 Evidence

Store evidence on a private disk.

Also consider:

- MIME and extension allowlists
- malware scanning
- encrypted object storage
- short-lived download URLs
- authorization checks
- access logs
- legal holds
- checksum verification

## ✉️ Notifications

Public reasons may be included in emails and blocked responses. Never place internal notes, private detection details, evidence URLs, or staff-only allegations in public reason fields.

Custom notification classes are application code and should be reviewed like any other security-sensitive integration.

## 🔄 Transaction boundaries

Enforcement and audit persistence are transactional. Side effects are scheduled after commit. Avoid moving external calls into the database transaction because they cannot be rolled back reliably.

## 📈 Escalation

The applied-escalation table prevents duplicate threshold execution. Keep its unique index intact.

## 🧹 Pruning

Pruning is destructive. Confirm appeal windows, retention obligations, incident-response needs, and evidence-preservation requirements before enabling it.

## 🚨 Vulnerability reporting

Report vulnerabilities privately according to the repository's `SECURITY.md`.
