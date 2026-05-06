<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Contract;

use Akankov\HtmlAst\Node\Node;

/**
 * Frozen-BC re-export of {@see \Akankov\HtmlAst\Printer\Printer}.
 *
 * Downstream packages should depend on this contract rather than the concrete
 * `Printer` interface. See {@see ParserContract} for the BC rationale.
 */
interface PrinterContract
{
    public function print(Node $node): string;
}
