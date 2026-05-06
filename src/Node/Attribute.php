<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Node;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * An element attribute.
 *
 * `quoteStyle` is preserved so the lossless printer can round-trip the
 * original quoting (PLAN §1, "drops trivia" gap of \Dom\HTMLDocument).
 *
 * `namespace` is used only for foreign content (SVG, MathML) — for HTML
 * elements it is always `null`.
 */
final readonly class Attribute extends Node
{
    public function __construct(
        ByteRange $range,
        public string $name,
        public string $value,
        public AttributeQuoteStyle $quoteStyle = AttributeQuoteStyle::Double,
        public ?string $namespace = null,
    ) {
        parent::__construct($range);
    }

    public function kind(): NodeKind
    {
        return NodeKind::Attribute;
    }
}
