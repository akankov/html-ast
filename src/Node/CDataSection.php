<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Node;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * A `<![CDATA[ ... ]]>` section. Per the HTML5 spec, CDATA only appears inside
 * foreign content (SVG, MathML); inside HTML proper it is parsed as a comment.
 */
final readonly class CDataSection extends Node
{
    public function __construct(
        ByteRange $range,
        public string $data,
    ) {
        parent::__construct($range);
    }

    public function kind(): NodeKind
    {
        return NodeKind::CDataSection;
    }
}
