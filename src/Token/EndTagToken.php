<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Token;

use Akankov\HtmlAst\Position\ByteRange;

final readonly class EndTagToken extends Token
{
    /**
     * @param list<TokenAttribute> $attributes WHATWG tokenizer permits attributes on end tags (parse error, but tokenizer must emit them)
     */
    public function __construct(
        ByteRange $range,
        string $raw,
        public string $tagName,
        public array $attributes = [],
        public bool $selfClosing = false,
    ) {
        parent::__construct(TokenKind::EndTag, $range, $raw);
    }
}
