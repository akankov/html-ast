<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Position;

/**
 * Side-table mapping byte offsets to (line, column) pairs.
 *
 * Built once per parse and shared across every node in the resulting tree, so
 * individual nodes only carry a {@see ByteRange} — line/column information is
 * computed on demand.
 */
final readonly class SourceMap
{
    /**
     * @param non-empty-string $source     the original input string
     * @param list<int>        $lineStarts byte offset of each line start (line 1 is at index 0)
     */
    public function __construct(
        public string $source,
        public array $lineStarts,
    ) {
    }

    /**
     * @return array{line: int, column: int}
     */
    public function lineColumn(int $offset): array
    {
        throw new \LogicException('not yet implemented');
    }

    public static function fromSource(string $source): self
    {
        throw new \LogicException('not yet implemented');
    }
}
