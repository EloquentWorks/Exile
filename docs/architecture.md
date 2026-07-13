# Architecture

Exile separates enforcement into several concepts:

- **Ban:** blocks access by account, exact IP, CIDR network, device fingerprint, or a combination.
- **Restriction:** blocks a specific capability such as login or posting, or marks an account as shadowed.
- **Strike:** adds active points and may trigger automatic escalation.
- **Warning:** records a user-facing moderation warning without directly restricting access.
- **Appeal:** tracks a user's request to review a ban.
- **Evidence:** stores metadata for files attached to bans, restrictions, strikes, warnings, or appeals.
- **Moderation action:** records an internal audit history.

Raw device fingerprints are never stored. Exact IP addresses are encrypted for administrative review and separately represented by keyed hashes for matching. CIDR ranges are encrypted and matched in application code.
