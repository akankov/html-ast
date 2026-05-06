<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Parser;

use Akankov\HtmlAst\Node\Document;
use Akankov\HtmlAst\Node\DocumentFragment;
use Akankov\HtmlAst\Position\SourceMap;
use Akankov\HtmlAst\Token\TokenStream;

/**
 * Envelope returned by every {@see Parser::parse} call.
 *
 * Bundling the tree, token stream, error stream, and source map into a single
 * object makes the recovery contract from PLAN §4 O7 enforceable: consumers
 * cannot get the tree without also being handed the error list.
 */
final readonly class ParseResult
{
    /**
     * @param list<ParseError> $errors empty in lenient mode when parse was clean; non-empty when recovery happened
     */
    public function __construct(
        public Document|DocumentFragment $tree,
        public TokenStream $tokens,
        public array $errors,
        public SourceMap $sourceMap,
    ) {
    }
}
