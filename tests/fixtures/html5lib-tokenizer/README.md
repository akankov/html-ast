# html5lib-tests tokenizer fixtures

Vendored from [html5lib/html5lib-tests](https://github.com/html5lib/html5lib-tests)
(`tokenizer/` directory, MIT license), fetched from `master` on 2026-06-12 by
`bin/fetch-html5lib-tests.php`. Re-run that script to refresh.

`xmlViolation.test` is deliberately excluded — it tests an XML-coercion
serialization contract this package does not implement.

Consumed by `tests/Conformance/Html5libTokenizerTest.php`. Current status:

- 6,806 cases vendored
- 6,690 runnable (Data state, no `lastStartTag`)
- 6,651 passing
- 39 skip-listed with reasons (CR/CRLF input normalization and per-state
  NUL → U+FFFD replacement — both land with M1.C; see the `SKIP` const)

Parse-error codes are not compared yet — that is the error-catalog
follow-up tracked in `PLAN.md` M1.B/M1.C.
