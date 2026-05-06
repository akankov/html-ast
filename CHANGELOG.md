# Changelog

All notable changes to `akankov/html-ast` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html)
with the explicit caveat that **0.x releases are unstable** — public API shape may
change in any minor version. The 1.0 commitment lands only after the API has been
frozen in production for at least two months.

## [Unreleased]

## [0.0.1] — 2026-05-06

> **Scaffold marker, not a usable release.** Every implementation class
> in this version intentionally throws `\LogicException('not yet implemented')`.
> Do not `composer require` this version expecting working code — it exists
> to claim the namespace, light up Packagist, and let the dogfood example
> depend on a stable artifact while M1 is in flight. Real parser code lands
> in `0.1.0`.

### Added

- Package layout: `composer.json`, `Makefile`, Docker tooling, GitHub Actions
  CI matrix (PHP 8.3 / 8.4 / 8.5).
- Static-analysis configs: PHPStan max, Phan with `ext-ast`, modern
  PHP-CS-Fixer (`.php-cs-fixer.dist.php`), Rector for PHP 8.3 with
  `RemoveUnusedPublicMethodParameterRector` skipped (incompatible with
  the stub-throwing pattern).
- Public API stubs across `Parser/`, `Node/`, `Token/`, `Position/`,
  `Visitor/`, `Printer/`, `Contract/` namespaces — 36 types total.
- `docs/design/api-v0.1.md` (M0 deliverable) — resolves open questions
  O1–O10 from `PLAN.md` with rationale, plus performance budget
  (≤2× parse, ≤3× memory vs. raw `\Dom\HTMLDocument`), dogfood plan,
  and license-heritage seed.
- `bin/bench-budget.php` skeleton (no-op until `NativeParser` lands in M1).
- Repo metadata: `README.md` with the "`nikic/php-parser` for HTML"
  framing per `PLAN.md` §2, `LICENSE` (MIT), `CONTRIBUTING.md`,
  `CODE_OF_CONDUCT.md`, `SECURITY.md`, `CREDITS.md`, `renovate.json`.

[Unreleased]: https://github.com/akankov/html-ast/compare/v0.0.1...HEAD
[0.0.1]: https://github.com/akankov/html-ast/releases/tag/v0.0.1
