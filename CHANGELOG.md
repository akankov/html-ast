# Changelog

All notable changes to `akankov/html-ast` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html)
with the explicit caveat that **0.x releases are unstable** — public API shape may
change in any minor version. The 1.0 commitment lands only after the API has been
frozen in production for at least two months.

## [Unreleased]

### Added

- **M1.B (part 2): html5lib-tests tokenizer conformance suite.** 6,806 cases
  vendored from html5lib/html5lib-tests (`tests/fixtures/html5lib-tokenizer/`,
  refreshable via `bin/fetch-html5lib-tests.php`); 6,690 run (Data state, no
  `lastStartTag`), **6,651 pass**, 39 are skip-listed with reasons (CR/CRLF
  input normalization and per-state NUL → U+FFFD replacement, both M1.C).
  Token sequences are compared; error-code comparison is the follow-up.
- **DOCTYPE public/system identifier states (§13.2.5.56–.66).** The M1.A
  shortcut sent everything after the doctype name to bogus-doctype with
  force-quirks; `<!DOCTYPE html PUBLIC "…" "…">` now parses its identifiers
  (case-insensitive PUBLIC/SYSTEM keywords, all four quoting states, the
  between/after states, spec error codes). Cleared ~317 conformance cases.
- **Comment-end-bang state (§13.2.5.52).** `<!--x--!>` and friends now
  follow the spec (`incorrectly-closed-comment`, `--!` data append).
- **M1.B (part 1): full WHATWG named-character-reference table.** The
  generated `Internal\Tokenizer\NamedCharacterReferences` holds all 2231
  entries, codegen'd by `bin/generate-entities.php` from the vendored
  `resources/entities.json` (canonical WHATWG data, sha256-stamped).
  `CharacterReference::matchNamed()` switched from a table scan to a
  longest-match probe over descending candidate lengths (≤31 hash lookups
  per `&`, O(1) in table size) to stay inside the committed performance
  budget.
- **Attribute-value character-reference rules (WHATWG §13.2.5.73).** A
  legacy (semicolon-less) named match inside an attribute value followed by
  `=` or an alphanumeric now stays literal (`?x=1&copy=2` keeps `&copy`),
  and semicolon-less matches elsewhere emit the spec's
  `missing-semicolon-after-character-reference` parse error.
- **M1.A: WHATWG HTML5 tokenizer with round-trip fidelity.**
  `Akankov\HtmlAst\Internal\Tokenizer\Tokenizer` produces a `TokenStream`
  where the concat of every token's `$raw` byte-for-byte equals the input —
  the differentiator from `\Dom\HTMLDocument` per PLAN §1.
- `Token` is now `abstract readonly` with eight subclasses
  (`StartTagToken`, `EndTagToken`, `CharacterToken`, `WhitespaceToken`,
  `CommentToken`, `DoctypeToken`, `CdataToken`, `EndOfFileToken`) plus
  `TokenAttribute` for tag attribute carrying. Each token kind is its own
  type for `instanceof` narrowing in PHPStan and Phan.
- Full numeric-reference decoding (named, decimal, hexadecimal) with the
  WHATWG C1-control replacement table.
- Round-trip baseline test: 10 fixtures in `tests/fixtures/tokenizer/`
  covering empty input, text, all four attribute quote styles, comments,
  DOCTYPE, CDATA in foreign content, script-data state, character
  references, malformed-tag recovery, and SVG.

### Fixed

- **Bare `&` at EOF looped until OOM.** `reconsume()` rewound the buffer
  even when the preceding `consume()` returned EOF without advancing, so
  Data → CharacterReference re-consumed the same `&` forever. `reconsume()`
  now only undoes an advancing consume — the spec's "reconsume EOF" means
  the next state sees EOF too. Found by the conformance suite's 47th case.
- **`WhitespaceToken` lost the decoded form.** Entities that decode to
  whitespace (`&Tab;`, `&#10;`) flushed as whitespace-only runs carrying
  only the raw bytes; the token now exposes `$data` alongside `$raw`.
- **Numeric character references above the int range crashed.** 65-bit
  references (`&#x10000000000000041;`) overflowed the accumulator to float;
  accumulation now saturates at 0x110000, which decodes to U+FFFD per spec.
- **`&#X` (uppercase) flushed as lowercase `&#x`** when no hex digits
  followed; the actually-consumed code points are flushed now.

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
