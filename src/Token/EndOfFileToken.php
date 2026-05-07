<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Token;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * The terminator. Always the final token in any {@see TokenStream}. `$raw`
 * is empty (nothing was consumed to emit it); `$range` is a zero-length
 * range at the end of the input.
 */
final readonly class EndOfFileToken extends Token
{
    public function __construct(
        ByteRange $range,
    ) {
        parent::__construct(TokenKind::EndOfFile, $range, '');
    }
}
