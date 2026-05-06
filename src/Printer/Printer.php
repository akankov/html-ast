<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Printer;

use Akankov\HtmlAst\Node\Node;

/**
 * Serialize an AST back to HTML.
 *
 * Two implementations are planned (PLAN §4 O6 lean (b)): {@see StandardPrinter}
 * for normalized output (M3) and a `LosslessPrinter` for trivia-exact
 * round-trips (deferred to v0.2).
 */
interface Printer
{
    public function print(Node $node): string;
}
