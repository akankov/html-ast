<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Token;

use Akankov\HtmlAst\Node\AttributeQuoteStyle;
use Akankov\HtmlAst\Position\ByteRange;

/**
 * An attribute as carried by a {@see StartTagToken}. Distinct from
 * {@see \Akankov\HtmlAst\Node\Attribute} (the AST-level node) — token
 * attributes capture exactly what the tokenizer saw, including duplicate
 * names (which the tree builder later collapses).
 */
final readonly class TokenAttribute
{
    public function __construct(
        public ByteRange $range,
        public string $name,
        public string $value,
        public AttributeQuoteStyle $quoteStyle,
    ) {
    }
}
