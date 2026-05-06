<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Node;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * Root of a full-document parse. `quirksMode` reflects the HTML5 spec's
 * quirks/no-quirks/limited-quirks classification, which downstream consumers
 * (e.g. CSS-style resolvers) need.
 */
final readonly class Document extends Node
{
    /**
     * @param list<Node> $children
     */
    public function __construct(
        ByteRange $range,
        public array $children = [],
        public QuirksMode $quirksMode = QuirksMode::NoQuirks,
    ) {
        parent::__construct($range);
    }

    public function kind(): NodeKind
    {
        return NodeKind::Document;
    }
}
