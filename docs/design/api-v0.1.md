# `akankov/html-ast` v0.1 API design

> **Status:** M0 deliverable. Resolves the ten open questions in
> [`PLAN.md`](../../PLAN.md) §4. Implementation against this design begins at M1.
>
> **Caveat:** the operator has chosen to start `html-ast` independently of the
> `html-min` v2 in-tree-AST prototype that PLAN §6 originally lists as the
> "extract from" prerequisite. This document records the design decisions made
> *without* that prototype, and the two mitigations that bound the resulting
> "designing in a vacuum" risk:
>
> 1. A toy consumer (`data-testid` stripper visitor) ships **before M1 wraps**
>    — see [§Dogfood plan](#dogfood-plan).
> 2. Every decision below is revisited after M3's `StandardPrinter` ships,
>    with a written reconciliation note appended to this file.

---

## O1. Parser source

**Decision:** ship a `Parser` interface with two implementations. `NativeParser`
wraps PHP 8.4's `\Dom\HTMLDocument` (lexbor) — the zero-dep happy path.
`MastermindsParser` wraps `masterminds/html5` — the PHP 8.3 fallback.
`masterminds/html5` is `suggest`, never `require`. A `Parser::detect()` factory
returns the right one for the running PHP version.

**Rationale:** matches the workspace 8.3 floor without forcing every consumer
to install a fallback they will never use. Two backends do mean two test
matrices, but the divergence surface is bounded — the public API in
`ParseResult`, `Node\`, `Token\`, and `Position\` is the same regardless of
backend; only the position-fidelity contract differs (see open follow-up).

**Pending follow-up (M4):** document every position-fidelity divergence between
the two backends — `masterminds/html5` does not surface byte offsets natively,
so `MastermindsParser` may produce coarser `ByteRange` data than `NativeParser`
on identical input. The reconciliation note belongs in this section after M4.

---

## O2. AST node shape

**Decision:** class hierarchy with readonly properties.
`abstract readonly class Node` is the base; every leaf type
(`Element`, `Text`, `Comment`, etc.) is `final readonly` and inherits
`public ByteRange $range`. Mutation is expressed by constructing a new node
and replacing the old one through the visitor return-value protocol — no
mutable methods, no parent pointers.

**Rationale:** matches the familiar PHP idiom from `nikic/php-parser` while
giving us the equational reasoning of immutability. Parent pointers are
deliberately rejected: they make structural sharing fragile (a re-parent on
one branch must rewrite every ancestor) and complicate the printer.

**Trade-off accepted:** mutations are slightly more verbose
(`$el->withAttributes(...)` instead of `$el->attributes[] = ...`), but the
pattern is consistent across every node and matches what consumers already
know from PSR-7 / PSR-11 / PSR-15 immutables.

**Deferred:** PHP 8.4 lazy objects for deferred subtree materialization on big
documents. Not adopted in v0.1 — revisit if memory budget pressure shows up
in the M5 benchmarks.

---

## O3. Position metadata granularity

**Decision:** byte offsets only on each node (via `ByteRange`); line and column
information is computed on demand through the `SourceMap` side-table.

**Rationale:** keeps node memory footprint minimal (two `int`s per node
instead of four) while preserving cheap `O(log n)` line/column lookups. Matches
the path `nikic/php-parser` evolved through over time. The `SourceMap` is
shared by reference across every node in a tree, so the per-document overhead
is constant.

**Pending follow-up (M5 benchmarks):** confirm the two-`int` `ByteRange`
overhead is below the 3× memory budget vs. raw `\Dom\HTMLDocument`. If not,
introduce a packed encoding (single `int` storing `start | end << 32`).

---

## O4. Trivia preservation

**Decision:** the token stream is a first-class artifact alongside the tree.
`ParseResult::$tokens` exposes the full `TokenStream`; the tree refers into it
by `ByteRange` rather than owning trivia directly.

**Rationale:** this is *the* differentiator vs. `\Dom\HTMLDocument`. A
formatter, a fidelity printer, or a source-map emitter is impossible without
it. Attaching trivia to nodes (lean (b)) was rejected because the attribution
is ambiguous — a comment between two siblings has no obvious owner — and
because it bloats every node with optional fields most consumers don't need.

**Trade-off accepted:** roughly double the parse-time data (tree + tokens),
which is a memory hit. The 3× memory budget in §Performance budget bounds
this.

---

## O5. Visitor API

**Decision:** nikic-style `enterNode` / `leaveNode` with permissive return
type `Node|VisitorAction|null`. `null` is a synonym for
`VisitorAction::Continue`.

**Rationale:** the muscle-memory match for PHP devs already comfortable with
`nikic/php-parser` is the strongest signal. Permissive null was chosen over
the strict `Node|VisitorAction`-only variant because most visitor methods
care about only a handful of node kinds — forcing every method body to end in
`return VisitorAction::Continue;` is ergonomic noise that compounds across
every consumer.

**Trade-off accepted:** `Node|VisitorAction|null` has three meanings depending
on which is returned, which PHPStan-max can complain about in some configs.
Acceptable cost for the API ergonomics.

```php
public function enterNode(Node $node): Node|VisitorAction|null;
public function leaveNode(Node $node): Node|VisitorAction|null;
```

`VisitorAction` enum: `Continue`, `SkipChildren`, `Stop`, `Remove`. To replace
a node, return the replacement `Node` directly.

**Deferred (v0.3 maybe):** streaming visitor (SAX-style start/end callbacks,
PLAN §4 O5 lean (c)). Doesn't fit the immutable-AST model; ship as a separate
streaming API if and when streaming is a real requirement.

---

## O6. Printer fidelity contract

**Decision:** two printers. `StandardPrinter` ships in M3 with normalized
output (canonical attribute quoting, doctype reduction, void-element
handling per HTML5). `LosslessPrinter` ships in v0.2 and round-trips trivia
exactly.

**Rationale:** mirrors the `nikic/php-parser` `Standard` vs.
`PrettyPrinter\FormatPreserving` split. A single configurable printer (lean
(c)) was rejected because the flag matrix would explode — you don't want one
class trying to choose between "preserve original quoting" and "normalize all
quotes" with a single boolean.

**Out of scope for v0.1:** lossless printer. Documented in
[`CHANGELOG.md`](../../CHANGELOG.md) as deferred.

---

## O7. Error model

**Decision:** configurable. Default = lenient. `ParseResult` is the only way to
get the tree, and it always includes `$errors: list<ParseError>` so consumers
cannot silently miss recovery.

```php
ParseOptions::document()->lenient();   // default
ParseOptions::document()->strict();    // throws on first error
```

**Rationale:** HTML5 mandates recovery in non-strict mode; throwing on the
first error fights the spec. Bundling errors into the result envelope (rather
than a separate accessor) makes them impossible to forget — you can't get the
`$tree` field without seeing `$errors` next to it.

---

## O8. Fragment vs. document parsing

**Decision:** both from day one. `ParseOptions::document()` and
`ParseOptions::fragment(string $context = 'body')`. Fragment context is the
element name the parser uses as the insertion-mode anchor.

**Rationale:** required for templating, components, SSE/Turbo Stream chunks —
all the use cases that would actually adopt `html-ast` in Wave 2 of the
ecosystem. Skipping this in v0.1 would push the package back another release.

---

## O9. Streaming / incremental parsing

**Decision:** out of scope for v0.1. Document and fragment parsing only,
all-at-once. Revisit in v0.2+ if and when `html-min` v3 (or another consumer)
needs it for SSE/Turbo Streams.

---

## O10. Naming the AST node types

**Decision:** spec names, scoped under `Akankov\HtmlAst\Node\`. So `Element`,
`Text`, `Comment`, `Doctype`, `ProcessingInstruction`, `CDataSection`,
`Document`, `DocumentFragment`. The namespace disambiguates from PHP's
overloaded `Element` / `Text` / etc.

**Rationale:** matches the HTML5 spec, matches `\Dom\HTMLDocument`, no extra
prefix cognitive load. `HtmlElement`-style disambiguation was rejected because
the namespace already does the job and the prefix becomes pure noise once you
have the `use` statement at the top of the file.

---

## Performance budget

The package is committed to staying within these bounds vs. raw
`\Dom\HTMLDocument` (which is the floor we cannot beat by definition since we
wrap it):

- **Parse time:** ≤ 2× raw `\Dom\HTMLDocument` parse time.
- **Memory:** ≤ 3× raw `\Dom\HTMLDocument` peak memory (the 3× headroom covers
  the token stream, the immutable AST node objects, and the source-map
  side-table).

Enforcement: `bin/bench-budget.php` walks `tests/fixtures/` from M1 onward,
parses every fixture both ways, and exits non-zero if any ratio breaches
budget. A nightly CI job runs it. Budget regressions block release.

If the budget cannot be met for the AST construction layer, the fallback is
PHP 8.4 lazy objects for deferred subtree materialization — but only after a
profiler has shown the breach is structural, not a missing optimization.

---

## Dogfood plan

The mitigation for the operator's gate-override decision (PLAN §6 + §7
"designing in a vacuum" risk):

1. **Before M1 wraps**, ship a toy consumer in
   `examples/strip-data-testid.php`: a visitor that removes every
   `data-testid` attribute from an arbitrary HTML input and prints the
   result. The example must compile against the public API only —
   no `Akankov\HtmlAst\Internal\` access.
2. If writing the example reveals API friction, fix the API *before* M1
   wraps — friction discovered during dogfood is a genuine design feedback
   signal, not a feature request.
3. **After M3 ships**, append a *Reconciliation* section to the bottom of this
   document. For each O1–O10, write one line: *"holds / drift / change"*
   with rationale. If any decision changed, the new behaviour is the
   committed contract for v0.1.

This is the cheap structural substitute for what the `html-min` v2 in-tree-AST
prototype was supposed to provide: a real consumer exercising the API before
the API freezes.

---

## License heritage seed

Per [`CREDITS.md`](../../CREDITS.md), every non-trivial algorithm ported from
prior art needs a documented row. The expected porting candidates for v0.1:

- **Visitor protocol** from `nikic/php-parser` (BSD-3-Clause). Direct port of
  the `enterNode` / `leaveNode` + sentinel-return-value pattern, expressed
  with PHP-native enums instead of integer constants.
- **Foreign-content insertion-mode logic** from `parse5` (MIT). Likely needed
  in `MastermindsParser` to bridge gaps where `masterminds/html5` does not
  fully implement the WHATWG spec for `<svg>` / `<math>` boundaries.
- **Position-tracking pattern** from `@swc/html` (Apache-2.0). The "byte range
  on node + side-table for line/column" pattern (our O3 decision) maps
  directly onto theirs; preserve `NOTICE` if we adopt their range encoding.

These are inspirations as of M0. Anything actually ported gets a `CREDITS.md`
row when the code lands.
