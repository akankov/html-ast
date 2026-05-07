<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Token;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * A run of character data — text between tags. `$raw` preserves the source
 * bytes (including unresolved or partially-resolved character references);
 * `$data` is the decoded form a consumer should emit when serializing.
 *
 * The tokenizer emits one CharacterToken per character-reference resolution
 * boundary, not per individual codepoint, so common ASCII text becomes a
 * single token. Whitespace-only runs are emitted as
 * {@see WhitespaceToken} (the tree builder uses the distinction to decide
 * whether trivia can be collapsed).
 */
final readonly class CharacterToken extends Token
{
    public function __construct(
        ByteRange $range,
        string $raw,
        public string $data,
    ) {
        parent::__construct(TokenKind::Character, $range, $raw);
    }
}
