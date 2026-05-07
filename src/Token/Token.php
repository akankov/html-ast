<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Token;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * A single tokenizer output. Tokens are first-class artifacts alongside the
 * tree (PLAN §4 O4 lean (c)) — the tree refers to token ranges rather than
 * owning the trivia, so a fidelity printer can replay the original byte
 * stream including whitespace between attributes, attribute quoting style,
 * and comment positions.
 *
 * Each concrete kind is its own subclass ({@see StartTagToken},
 * {@see EndTagToken}, {@see CharacterToken}, {@see WhitespaceToken},
 * {@see CommentToken}, {@see DoctypeToken}, {@see CdataToken},
 * {@see EndOfFileToken}). PHPStan / Phan instanceof-narrowing makes
 * consumer code clean while keeping per-kind data type-safe.
 *
 * Round-trip fidelity contract: for any tokenizer output `$stream`,
 * `implode('', array_map(fn($t) => $t->raw, $stream->tokens))` must equal
 * the original input bytes.
 */
abstract readonly class Token
{
    public function __construct(
        public TokenKind $kind,
        public ByteRange $range,
        public string $raw,
    ) {
    }
}
