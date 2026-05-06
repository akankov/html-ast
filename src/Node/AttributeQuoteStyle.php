<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Node;

/**
 * Quoting style of an attribute value, preserved on parse so the lossless
 * printer can reproduce it. The HTML5 spec permits all four forms; the
 * standard printer normalizes to {@see self::Double}.
 */
enum AttributeQuoteStyle: string
{
    case Double = 'double';
    case Single = 'single';
    case Unquoted = 'unquoted';
    case Empty = 'empty';
}
