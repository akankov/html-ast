<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Token;

use Akankov\HtmlAst\Position\ByteRange;

final readonly class CommentToken extends Token
{
    public function __construct(
        ByteRange $range,
        string $raw,
        public string $data,
    ) {
        parent::__construct(TokenKind::Comment, $range, $raw);
    }
}
