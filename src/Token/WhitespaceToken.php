<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Token;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * Whitespace-only character run. Emitted as a separate token kind from
 * {@see CharacterToken} so the tree builder can decide whether the trivia
 * is collapsible (most insertion modes drop pure-whitespace text in
 * positions where it doesn't matter).
 */
final readonly class WhitespaceToken extends Token
{
    public function __construct(
        ByteRange $range,
        string $raw,
        /**
         * Decoded whitespace. Usually identical to $raw, but differs when a
         * character reference decoded to whitespace (`&Tab;` → "\t") — raw
         * keeps the source bytes, this keeps what they mean.
         */
        public string $data,
    ) {
        parent::__construct(TokenKind::Whitespace, $range, $raw);
    }
}
