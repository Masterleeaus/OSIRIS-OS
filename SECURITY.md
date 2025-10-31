# Security Policy

## Reporting a Vulnerability
If you discover a security issue, please **do not create a public GitHub issue**. Instead:
- Email: security@rechain.network (PGP-encrypted if possible)
- Include: affected component, steps to reproduce, impact, PoC if available.

We will acknowledge within 48 hours and provide a timeline.

## Supported Versions
We maintain security fixes for the latest two minor releases. For older releases contact maintainers.

## Disclosure Policy
We coordinate responsible disclosure. Public disclosure will only happen after a fix or agreed timeline.

## Known hardening
- Use of signed release artifacts
- Regular dependency scans (GHAS / Dependabot)
- Container image scanning in CI
