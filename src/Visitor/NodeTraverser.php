<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Visitor;

use Akankov\HtmlAst\Node\Node;

/**
 * Drives a depth-first traversal of an AST, invoking each registered visitor's
 * `enterNode` and `leaveNode` methods and applying their return values
 * ({@see VisitorAction} sentinels or replacement {@see Node} instances) to
 * the tree.
 *
 * The traverser is stateless across calls — every `traverse()` invocation
 * produces a fresh tree with structural sharing where no mutations occurred,
 * matching the immutable-AST contract from PLAN §4 O2 lean (a).
 */
final class NodeTraverser
{
    /**
     * @param list<Visitor> $visitors visitors are run in order on each node
     *
     * @return Node the (possibly transformed) root
     */
    public function traverse(Node $root, array $visitors): Node
    {
        throw new \LogicException('not yet implemented');
    }
}
