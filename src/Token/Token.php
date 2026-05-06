<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Token;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * A single tokenizer output. Tokens are first-class artifacts alongside the
 * tree (PLAN §4 O4 lean (c)) — the tree refers to token ranges rather than
 * owning the trivia, so a fidelity printer can replay the original byte stream
 * including whitespace between attributes, attribute quoting style, and
 * comment positions.
 */
final readonly class Token
{
    public function __construct(
        public TokenKind $kind,
        public ByteRange $range,
        public string $raw,
    ) {
    }
}
