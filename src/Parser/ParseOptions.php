<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Parser;

/**
 * Options passed to {@see Parser::parse}.
 *
 * Constructed via the named factories instead of a public constructor so the
 * public API can grow new dimensions without churning every call site
 * (PLAN §4 O7 lean (c) — strict/lenient is configurable; default is lenient).
 */
final readonly class ParseOptions
{
    public function __construct(
        public ParseMode $mode,
        public string $fragmentContext,
        public bool $strict,
    ) {
    }

    public static function document(): self
    {
        return new self(ParseMode::Document, '', false);
    }

    public static function fragment(string $context = 'body'): self
    {
        return new self(ParseMode::Fragment, $context, false);
    }

    public function strict(): self
    {
        return new self($this->mode, $this->fragmentContext, true);
    }

    public function lenient(): self
    {
        return new self($this->mode, $this->fragmentContext, false);
    }
}
