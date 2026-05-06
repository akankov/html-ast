# `akankov/html-ast`

> **`nikic/php-parser` for HTML.** A spec-compliant HTML5 abstract syntax tree
> for PHP, with byte-range positions, trivia preservation, an immutable visitor
> framework, and a fidelity printer.

[![CI](https://github.com/akankov/html-ast/actions/workflows/ci.yml/badge.svg)](https://github.com/akankov/html-ast/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/akankov/html-ast.svg)](https://packagist.org/packages/akankov/html-ast)
[![PHP Version](https://img.shields.io/packagist/php-v/akankov/html-ast.svg)](https://packagist.org/packages/akankov/html-ast)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> ⚠️ **0.x is unstable.** The public API shape may change in any minor release
> until the package reaches `1.0`. The 1.0 commitment lands only after the API
> has been frozen in production for at least two months.

## Why this exists

PHP 8.4 ships native HTML5 parsing through `\Dom\HTMLDocument` (lexbor under
the hood). That collapses a decade of fragmented PHP HTML tooling — but
`\Dom\HTMLDocument` is a great parser, not a great AST for transformation
work. It has four gaps that `html-ast` fills:

1. **Byte-range positions on every node.** Required for linters, formatters,
   and source maps. `\Dom\HTMLDocument` exposes none.
2. **Trivia preservation** (whitespace between attributes, comment positions,
   attribute quoting style). Required for round-trip fidelity. The native
   serializer drops it.
3. **An immutable visitor framework** with `enterNode` / `leaveNode` and
   sentinel return values for replace, remove, and stop. Familiar to anyone
   who has written a `nikic/php-parser` visitor.
4. **A fidelity printer.** `StandardPrinter` produces normalized output;
   `LosslessPrinter` (v0.2) round-trips trivia exactly.

It also bridges PHP 8.3 (via a `masterminds/html5` adapter) and PHP 8.4+
(via the native parser) behind a single `Parser` interface, so consumers do
not need to branch.

## Install

```bash
composer require akankov/html-ast
```

PHP 8.3 users additionally need:

```bash
composer require masterminds/html5:^2.9
```

(On PHP 8.4+ the native `\Dom\HTMLDocument` backend is used and there are
zero runtime dependencies.)

## 30-second example

```php
use Akankov\HtmlAst\Parser\Parser;
use Akankov\HtmlAst\Node\Element;
use Akankov\HtmlAst\Visitor\Visitor;
use Akankov\HtmlAst\Visitor\NodeTraverser;
use Akankov\HtmlAst\Visitor\VisitorAction;
use Akankov\HtmlAst\Printer\StandardPrinter;

$result = Parser::detect()->parse($html);

$stripTestIds = new class implements Visitor {
    public function enterNode(\Akankov\HtmlAst\Node\Node $n): VisitorAction|\Akankov\HtmlAst\Node\Node|null
    {
        if ($n instanceof Element && $n->hasAttribute('data-testid')) {
            return $n->withoutAttribute('data-testid');
        }
        return null;
    }

    public function leaveNode(\Akankov\HtmlAst\Node\Node $n): VisitorAction|\Akankov\HtmlAst\Node\Node|null
    {
        return null;
    }
};

$tree   = (new NodeTraverser())->traverse($result->tree, [$stripTestIds]);
$output = (new StandardPrinter())->print($tree);
```

## Status

`akankov/html-ast` is in the **M0 design phase** — the public API shape is
being resolved at [`docs/design/api-v0.1.md`](docs/design/api-v0.1.md). All
implementation classes currently throw `\LogicException` so type checkers
pass while the algorithms are being written. Track the progress on the
[milestones page](https://github.com/akankov/html-ast/milestones).

## Documentation

- **API design** — [`docs/design/api-v0.1.md`](docs/design/api-v0.1.md).
- **Algorithm lineage** — [`CREDITS.md`](CREDITS.md).
- **Contributing** — [`CONTRIBUTING.md`](CONTRIBUTING.md).
- **Security** — [`SECURITY.md`](SECURITY.md).
- **Changelog** — [`CHANGELOG.md`](CHANGELOG.md).

## License

MIT, see [`LICENSE`](LICENSE).
