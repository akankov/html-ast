# Credits

This file documents the lineage of any non-trivial algorithm or design pattern
that `akankov/html-ast` has adapted from prior art. Maintaining it is both a
license-compliance discipline and a way to make the project's "original
contribution" claim concrete: it shows exactly what was adapted and what was
designed from scratch.

Add a row whenever you port, translate, or substantially adapt code or an
algorithm from another project. Style improvements and trivial idioms do not
need to be listed.

| Source | License | What we adapted | Where in `src/` |
| ------ | ------- | --------------- | --------------- |
| _none yet_ | _—_ | _—_ | _—_ |

## Reference projects studied (not necessarily adapted)

These projects informed the design phase. Anything actually ported gets its
own row in the table above; this list exists so future contributors know which
prior art to study before proposing changes.

- [`nikic/PHP-Parser`](https://github.com/nikic/PHP-Parser) — BSD-3-Clause.
  Visitor protocol (`enterNode`/`leaveNode` with sentinel return values),
  printer architecture, attribute-bag pattern for positions and trivia.
- [`parse5`](https://github.com/inikulin/parse5) — MIT. Tree-adapter pattern,
  WHATWG conformance test methodology.
- [`@swc/html`](https://github.com/swc-project/swc) — Apache-2.0. AST shape
  with byte-range positions, `visit_mut` pattern.
- [`html5ever` / `markup5ever`](https://github.com/servo/html5ever) —
  MIT/Apache-2.0. Spec-compliant tokenization and tree-construction reference.
- [`htmlparser2`](https://github.com/fb55/htmlparser2) +
  [`domhandler`](https://github.com/fb55/domhandler) — MIT. SAX-with-tree
  pattern (informs the deferred streaming design).
- [`masterminds/html5`](https://github.com/Masterminds/html5-php) — MIT. Used
  as the 8.3 fallback parser via a thin adapter; not ported.
- Lexbor (the engine inside PHP 8.4's `\Dom\HTMLDocument`) — Apache-2.0. Used
  through PHP's binding; nothing ported.
