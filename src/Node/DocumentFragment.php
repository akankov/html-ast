<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Node;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * Root of a fragment parse (PLAN §4 O8 — fragments are first-class from
 * v0.1). The `context` is the element name that the parser used as the
 * insertion-mode anchor (`'body'`, `'template'`, etc.).
 */
final readonly class DocumentFragment extends Node
{
    /**
     * @param list<Node> $children
     */
    public function __construct(
        ByteRange $range,
        public string $context,
        public array $children = [],
    ) {
        parent::__construct($range);
    }

    public function kind(): NodeKind
    {
        return NodeKind::DocumentFragment;
    }
}
