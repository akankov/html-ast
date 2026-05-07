<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Token;

use Akankov\HtmlAst\Position\ByteRange;

final readonly class StartTagToken extends Token
{
    /**
     * @param list<TokenAttribute> $attributes the attribute list as the tokenizer saw it (may contain duplicate names)
     */
    public function __construct(
        ByteRange $range,
        string $raw,
        public string $tagName,
        public array $attributes,
        public bool $selfClosing,
    ) {
        parent::__construct(TokenKind::StartTag, $range, $raw);
    }
}
