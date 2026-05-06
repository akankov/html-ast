# Contributing

Thanks for helping improve `akankov/html-ast`. The library is in `0.x` (unstable
shape) — small focused changes with clear tests are easiest to review, and
breaking-API discussions belong in `docs/design/api-v0.1.md` before a PR.

## Reporting Bugs

Open a bug report when:

- A parser produces a tree that diverges from the HTML5 spec on a documented
  fixture.
- Round-trip fidelity (parse → print) loses or alters trivia in a way the
  printer contract says it should preserve.
- Position metadata (`ByteRange` / `SourceMap`) returns offsets that disagree
  with the input string.
- Static analysis (PHPStan max, Phan, Rector) regresses against tagged
  releases.

Include:

- The package version or commit SHA.
- The PHP version and which parser backend is in use (`NativeParser` on 8.4+
  or `MastermindsParser` on 8.3).
- The smallest input HTML that reproduces the issue.
- The actual output and expected output.
- Any related parser warnings or stack traces.

Please do not report security vulnerabilities in public issues. Use the process
in [SECURITY.md](SECURITY.md) instead.

## Requesting Features

Before opening a feature request, check `docs/design/api-v0.1.md` to see whether
the open question has already been resolved or explicitly deferred. Feature
requests should describe:

- The HTML pattern.
- The expected AST or printer output.
- Why the behavior belongs in this library rather than in a downstream
  consumer (linter, formatter, minifier).

## Development Setup

Tooling runs through pinned Docker images so the matrix is reproducible:

```bash
make install
make test          # PHPUnit on the default PHP_VERSION (8.4)
make test-all      # PHPUnit on PHP 8.3, 8.4, 8.5
make phpstan       # PHPStan level max
make phan          # Phan with ext-ast (builds the docker image first)
make cs-check      # PHP-CS-Fixer dry-run
make rector-check  # Rector dry-run
make ci            # Full CI pipeline locally
```

## Tests

Add regression coverage for any behavior change. For parser/printer
input/output cases, prefer fixtures in `tests/fixtures/` named
`<name>.input.html` and `<name>.expected.html`, then add a PHPUnit
data-provider case.

Keep tests compatible with PHP 8.3. The Composer platform and Rector config are
pinned to PHP 8.3 even when local checks also run newer versions.

## Algorithm porting

If you port a non-trivial algorithm from `nikic/php-parser`, `parse5`,
`@swc/html`, `html5ever`, or any other prior art, **add an entry to
`CREDITS.md`** with the source, license, and what was adapted. This is both a
license-compliance requirement and how the project documents its lineage.

## Pull Requests

Before opening a pull request:

- Keep the change focused and explain the user-visible behavior.
- Add or update tests for parser, visitor, or printer regressions.
- Update `docs/design/api-v0.1.md` if the change touches a previously resolved
  open question.
- Run the relevant checks and mention any that could not be run.
- Link the related issue when there is one.
