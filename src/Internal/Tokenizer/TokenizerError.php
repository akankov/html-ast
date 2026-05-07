<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Internal\Tokenizer;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * Tokenizer-level error captured during recovery. M1.B converts these into
 * the public {@see \Akankov\HtmlAst\Parser\ParseError} on its way to
 * {@see \Akankov\HtmlAst\Parser\ParseResult::$errors}.
 *
 * The codes mirror WHATWG's named tokenizer errors (§13.2.5 "Parse errors")
 * but are deliberately a string enum here, not an enum class, because the
 * full code list is large (~70) and the M1.A subset is only a handful.
 *
 * @internal no BC guarantees
 */
final readonly class TokenizerError
{
    public function __construct(
        public string $code,
        public ByteRange $range,
        public string $message,
    ) {
    }
}
