<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Parser;

/**
 * Document or fragment? Per PLAN §4 O8 lean (b), both modes are first-class
 * from v0.1 — fragment parsing is required for templates, components, and
 * SSE/Turbo Stream chunks.
 */
enum ParseMode: string
{
    case Document = 'document';
    case Fragment = 'fragment';
}
