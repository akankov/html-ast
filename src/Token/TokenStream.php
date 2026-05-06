<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Token;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * Immutable, indexable sequence of tokens.
 *
 * Constructed by the parser and exposed through {@see \Akankov\HtmlAst\Parser\ParseResult::$tokens}
 * so consumers (formatters, lossless printers, source-map emitters) can replay
 * the original byte stream alongside the tree.
 */
final readonly class TokenStream
{
    /**
     * @param list<Token> $tokens
     */
    public function __construct(
        public array $tokens,
    ) {
    }

    public function count(): int
    {
        return \count($this->tokens);
    }

    /**
     * Return the slice of tokens fully contained inside the given range.
     *
     * @return list<Token>
     */
    public function slice(ByteRange $range): array
    {
        throw new \LogicException('not yet implemented');
    }
}
