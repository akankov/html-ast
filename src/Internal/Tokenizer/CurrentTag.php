<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Internal\Tokenizer;

use Akankov\HtmlAst\Node\AttributeQuoteStyle;
use Akankov\HtmlAst\Token\TokenAttribute;

/**
 * Mutable scratch builder for the WHATWG "current tag token" concept.
 *
 * Lives only inside the tokenizer; emitted as an immutable
 * {@see \Akankov\HtmlAst\Token\StartTagToken} or
 * {@see \Akankov\HtmlAst\Token\EndTagToken} once the `>` is consumed.
 *
 * @internal no BC guarantees
 */
final class CurrentTag
{
    public string $tagName = '';

    /** @var list<TokenAttribute> */
    public array $attributes = [];

    public bool $selfClosing = false;
    public bool $isEndTag = false;
    public int $start = 0;
    public string $currentAttrName = '';
    public string $currentAttrValue = '';
    public AttributeQuoteStyle $currentAttrQuote = AttributeQuoteStyle::Empty;
    public int $currentAttrStart = 0;
}
