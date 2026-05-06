<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Parser;

/**
 * The parser contract. Two implementations ship with the package:
 *
 * - {@see NativeParser} — uses PHP 8.4's native `\Dom\HTMLDocument`. Zero
 *   runtime deps. The preferred backend.
 * - {@see MastermindsParser} — pure-PHP fallback using `masterminds/html5`,
 *   for PHP 8.3 (where `\Dom\HTMLDocument` is not yet available). Requires
 *   the optional `masterminds/html5` dependency.
 *
 * Use {@see self::detect()} to pick the right one for the running PHP
 * version automatically.
 */
interface Parser
{
    public function parse(string $html, ?ParseOptions $options = null): ParseResult;

    /**
     * Auto-select an implementation based on the running PHP version and the
     * libraries present in `composer.json`.
     */
    public static function detect(): self;
}
