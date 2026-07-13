# Security Considerations

- Configure trusted proxies correctly before relying on `Request::ip()`.
- Use a dedicated, private `EXILE_HASH_KEY`.
- Do not expose private moderator notes to affected users.
- Apply authorization policies around issuing, revoking, approving, and viewing enforcement records.
- Store evidence on a private disk and use temporary URLs when files must be reviewed.
- Rate-limit appeals and moderation endpoints.
- Device fingerprints may be unstable or spoofed and should not be treated as proof of identity.
- CIDR bans can affect innocent users on shared networks; use them carefully.
- Shadow moderation should comply with your application's policies and applicable law.
