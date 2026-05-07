<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Token;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * The `<!DOCTYPE ...>` token. `$forceQuirks` is the WHATWG flag the tree
 * builder uses to set `Document::$quirksMode`; tokens themselves don't
 * resolve quirks/no-quirks/limited-quirks classification — only the tree
 * builder (M2) does.
 */
final readonly class DoctypeToken extends Token
{
    public function __construct(
        ByteRange $range,
        string $raw,
        public ?string $name,
        public ?string $publicId,
        public ?string $systemId,
        public bool $forceQuirks,
    ) {
        parent::__construct(TokenKind::Doctype, $range, $raw);
    }
}
