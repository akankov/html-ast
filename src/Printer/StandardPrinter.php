<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Printer;

use Akankov\HtmlAst\Node\Node;

/**
 * Normalized HTML output: canonical attribute quoting (double quotes),
 * doctype reduction, void-element handling per the HTML5 spec, no trivia
 * preservation. Implementation lands in M3.
 *
 * For round-trip fidelity (preserving original whitespace, comment positions,
 * attribute quoting style, and CDATA boundaries in foreign content) use
 * `LosslessPrinter` instead — it ships in v0.2 (PLAN §6 "Out of scope for v0.1").
 */
final class StandardPrinter implements Printer
{
    public function print(Node $node): string
    {
        throw new \LogicException('not yet implemented');
    }
}
