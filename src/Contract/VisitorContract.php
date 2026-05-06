<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Contract;

use Akankov\HtmlAst\Node\Node;
use Akankov\HtmlAst\Visitor\VisitorAction;

/**
 * Frozen-BC re-export of {@see \Akankov\HtmlAst\Visitor\Visitor}.
 *
 * Downstream packages writing reusable visitors should depend on this contract
 * rather than the concrete `Visitor` interface. See {@see ParserContract} for
 * the BC rationale.
 */
interface VisitorContract
{
    public function enterNode(Node $node): Node|VisitorAction|null;

    public function leaveNode(Node $node): Node|VisitorAction|null;
}
