# Security Policy

## Supported Versions

`akankov/html-ast` is currently in `0.x` (unstable). Security fixes land on the
latest `0.x` release. Once `1.0` is cut, fixes will land on the latest `1.x`
release, and older majors will only be patched once a `2.x` exists, and only for
critical issues.

| Version | Supported |
| ------- | --------- |
| 0.x     | ✅        |

## Reporting a Vulnerability

**Please do not open a public GitHub issue for security problems.**

Report vulnerabilities privately via GitHub's private reporting:

1. Go to the repo's [Security tab](https://github.com/akankov/html-ast/security).
2. Click **Report a vulnerability**.
3. Describe the issue, affected versions, and a proof-of-concept if you
   have one.

If GitHub's private reporting is unavailable to you, email
<akankov@gmail.com> instead.

## What to expect

- **Acknowledgement**: within 5 business days.
- **Triage & severity assessment**: within 10 business days.
- **Fix timeline**: depends on severity. Critical issues get a patch
  release as soon as a fix is verified; low-severity issues may be
  bundled into the next regular release.
- **Disclosure**: coordinated. We'll publish a GitHub Security Advisory
  (GHSA) crediting the reporter once a fix is released, unless you
  request otherwise.

## Scope

Findings in scope:

- Parser behavior that breaks HTML semantics in a security-relevant way
  (e.g. tree-construction divergence from the HTML5 spec that enables
  XSS escape bypass, comment / CDATA boundary confusion, foreign-content
  insertion-mode mistakes).
- Denial-of-service via pathological input (catastrophic regex,
  exponential blowup, unbounded memory or recursion).
- Vulnerabilities in runtime dependencies that this library exposes.
- Unsafe defaults in the printer (`StandardPrinter` / `LosslessPrinter`)
  that could allow an attacker-controlled tree to round-trip into XSS.

Out of scope:

- Issues in `\Dom\HTMLDocument` (PHP core, 8.4+) — report those upstream
  to <https://github.com/php/php-src>.
- Issues in `masterminds/html5` upstream when used as the 8.3 fallback
  parser — report those to
  [Masterminds/html5-php](https://github.com/Masterminds/html5-php).
- Issues that require a malicious maintainer to already be running code
  on your system.
- Findings in the dev-only toolchain (PHPUnit, PHPStan, Phan, etc.)
  unless they affect library output.
