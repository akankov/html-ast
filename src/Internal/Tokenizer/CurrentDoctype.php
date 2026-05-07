<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Internal\Tokenizer;

/**
 * Mutable scratch builder for the WHATWG "current DOCTYPE token" concept.
 *
 * Emitted as an immutable {@see \Akankov\HtmlAst\Token\DoctypeToken} once
 * the `>` is consumed.
 *
 * @internal no BC guarantees
 */
final class CurrentDoctype
{
    public ?string $name = null;
    public ?string $publicId = null;
    public ?string $systemId = null;
    public bool $forceQuirks = false;
    public int $start = 0;
}
