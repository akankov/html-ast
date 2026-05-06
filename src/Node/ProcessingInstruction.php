<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Node;

use Akankov\HtmlAst\Position\ByteRange;

final readonly class ProcessingInstruction extends Node
{
    public function __construct(
        ByteRange $range,
        public string $target,
        public string $data,
    ) {
        parent::__construct($range);
    }

    public function kind(): NodeKind
    {
        return NodeKind::ProcessingInstruction;
    }
}
