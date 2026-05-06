<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Parser;

/**
 * Backed by PHP 8.4's native `\Dom\HTMLDocument` (lexbor under the hood).
 *
 * Implementation lands in M1. The wrapper is responsible for:
 * - tokenizing alongside the tree build (since `\Dom\HTMLDocument` does not
 *   expose its token stream, we run a lightweight tokenizer pass in parallel
 *   to populate {@see \Akankov\HtmlAst\Token\TokenStream});
 * - converting `\Dom\Node` instances into our immutable
 *   {@see \Akankov\HtmlAst\Node\Node} hierarchy with attached
 *   {@see \Akankov\HtmlAst\Position\ByteRange} metadata;
 * - capturing parse errors from the native parser's error log (8.4+ exposes
 *   them through `\Dom\HTMLDocument::$errors`).
 */
final class NativeParser implements Parser
{
    public function parse(string $html, ?ParseOptions $options = null): ParseResult
    {
        throw new \LogicException('not yet implemented');
    }

    public static function detect(): Parser
    {
        throw new \LogicException('not yet implemented');
    }
}
