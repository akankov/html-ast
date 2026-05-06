<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Node;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * Base class for every AST node.
 *
 * Nodes are immutable: mutation is expressed by constructing a new node and
 * replacing the old one via a {@see \Akankov\HtmlAst\Visitor\Visitor} return
 * value (PLAN §4 O2 lean (a) — class hierarchy with readonly properties).
 *
 * Position metadata is a single {@see ByteRange}; line/column information is
 * computed on demand through {@see \Akankov\HtmlAst\Position\SourceMap}
 * (PLAN §4 O3 lean (c)).
 */
abstract readonly class Node
{
    public function __construct(
        public ByteRange $range,
    ) {
    }

    abstract public function kind(): NodeKind;
}
