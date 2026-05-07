<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Token;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * A `<![CDATA[ ... ]]>` section. Per HTML5 spec these are only valid inside
 * foreign content (SVG, MathML); inside HTML proper the same byte sequence
 * tokenizes as a {@see CommentToken} (`bogus comment` state).
 */
final readonly class CdataToken extends Token
{
    public function __construct(
        ByteRange $range,
        string $raw,
        public string $data,
    ) {
        parent::__construct(TokenKind::Cdata, $range, $raw);
    }
}
