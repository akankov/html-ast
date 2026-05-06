<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Parser;

/**
 * PHP 8.3 fallback backed by `masterminds/html5`. Implementation lands in M4.
 *
 * `masterminds/html5` is a `suggest`, not a `require`, so this class exists
 * but the constructor must guard against the dependency being absent and
 * throw a clear error directing the user to `composer require
 * masterminds/html5:^2.9`.
 *
 * Position-fidelity reconciliation between `NativeParser` and this backend
 * is part of M4's deliverable; documented divergences (e.g. byte ranges
 * may be coarser when masterminds doesn't surface them) live in
 * `docs/design/api-v0.1.md` §O1.
 */
final class MastermindsParser implements Parser
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
