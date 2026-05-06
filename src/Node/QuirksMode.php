<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Node;

/**
 * The HTML5 spec's three-way quirks classification (WHATWG §13.2.5.1).
 * Determined by the doctype and propagated to the document root.
 */
enum QuirksMode: string
{
    case NoQuirks = 'no_quirks';
    case Quirks = 'quirks';
    case LimitedQuirks = 'limited_quirks';
}
