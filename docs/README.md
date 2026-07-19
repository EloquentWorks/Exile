# Laravel Exile Documentation

Laravel Exile is a moderation-enforcement toolkit for Laravel applications.

## Start here

1. [Installation](installation.md)
2. [Configuration](configuration.md)
3. [Architecture](architecture.md)

## Enforcement

- [Bans](bans.md)
- [IP, network, and device enforcement](identifiers.md)
- [Restrictions](restrictions.md)
- [Warnings, strikes, and escalation](warnings-and-strikes.md)

## Moderation workflows

- [Appeals](appeals.md)
- [Evidence](evidence.md)
- [Middleware](middleware.md)
- [Events, notifications, and audit history](events-notifications-and-audit.md)

## Operations and extension

- [Commands and scheduling](commands-and-scheduling.md)
- [Customization](customization.md)
- [Security](security.md)
- [Testing](testing.md)
- [Release checklist](release-checklist.md)

## Package concepts

| Concept | Purpose |
| --- | --- |
| Ban | Blocks access through account, IP, network, device, or combined identifiers |
| Restriction | Blocks a particular capability without necessarily blocking the account |
| Warning | Communicates a moderation decision and may be acknowledged |
| Strike | Adds active points that can trigger escalation |
| Applied escalation | Reserves an account/threshold combination to prevent duplicate escalation |
| Appeal | Requests human review of a ban |
| Evidence | Stores file metadata and a SHA-256 integrity checksum |
| Moderation action | Provides an audit record for package activity |
