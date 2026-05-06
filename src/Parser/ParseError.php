<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Parser;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * A non-fatal parse error.
 *
 * In `lenient` mode (the default, per PLAN §4 O7 lean (c)) the parser recovers
 * from these and emits them as a list on {@see ParseResult::$errors} so the
 * tree is always available. In `strict` mode the parser throws on the first
 * such error instead.
 */
final readonly class ParseError
{
    public function __construct(
        public ParseErrorKind $kind,
        public ByteRange $range,
        public string $message,
    ) {
    }
}
