<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Contract;

use Akankov\HtmlAst\Parser\ParseOptions;
use Akankov\HtmlAst\Parser\ParseResult;

/**
 * Frozen-BC re-export of {@see \Akankov\HtmlAst\Parser\Parser}.
 *
 * Downstream packages that need to typehint against the parser interface (for
 * dependency injection, testing seams, etc.) should depend on this contract
 * rather than the concrete `Parser` interface. The `Parser\` namespace can
 * still evolve under semver-minor; types in this `Contract\` namespace are
 * frozen until a major bump.
 */
interface ParserContract
{
    public function parse(string $html, ?ParseOptions $options = null): ParseResult;
}
