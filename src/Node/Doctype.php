<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Node;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * The document doctype. For modern HTML5 documents `name` is `'html'` and the
 * public/system identifiers are `null`; the legacy strings exist only to
 * preserve fidelity for pre-HTML5 input.
 */
final readonly class Doctype extends Node
{
    public function __construct(
        ByteRange $range,
        public string $name,
        public ?string $publicId = null,
        public ?string $systemId = null,
    ) {
        parent::__construct($range);
    }

    public function kind(): NodeKind
    {
        return NodeKind::Doctype;
    }
}
