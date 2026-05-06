<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Position;

/**
 * A half-open byte range over the original source string: [start, end).
 *
 * Offsets are zero-based byte indexes into the input passed to the parser, not
 * character indexes — this keeps the contract independent of the input
 * encoding (HTML5 mandates UTF-8 but the parser sees the raw bytes).
 *
 * Line and column information is not stored on the node; query it through
 * {@see SourceMap} when needed (per PLAN §4 O3, lean (c)).
 */
final readonly class ByteRange
{
    public function __construct(
        public int $start,
        public int $end,
    ) {
    }

    public function length(): int
    {
        return $this->end - $this->start;
    }

    public function contains(int $offset): bool
    {
        return $offset >= $this->start && $offset < $this->end;
    }
}
