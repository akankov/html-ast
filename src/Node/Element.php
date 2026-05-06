<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Node;

use Akankov\HtmlAst\Position\ByteRange;

/**
 * An element node. Tag names are lowercased for HTML elements; foreign
 * content (SVG, MathML) preserves spec-defined casing.
 */
final readonly class Element extends Node
{
    /**
     * @param list<Attribute> $attributes
     * @param list<Node>      $children
     */
    public function __construct(
        ByteRange $range,
        public string $tagName,
        public array $attributes = [],
        public array $children = [],
        public ?string $namespace = null,
    ) {
        parent::__construct($range);
    }

    public function kind(): NodeKind
    {
        return NodeKind::Element;
    }

    public function hasAttribute(string $name): bool
    {
        throw new \LogicException('not yet implemented');
    }

    public function getAttribute(string $name): ?Attribute
    {
        throw new \LogicException('not yet implemented');
    }

    public function withoutAttribute(string $name): self
    {
        throw new \LogicException('not yet implemented');
    }

    /**
     * @param list<Attribute> $attributes
     */
    public function withAttributes(array $attributes): self
    {
        throw new \LogicException('not yet implemented');
    }

    /**
     * @param list<Node> $children
     */
    public function withChildren(array $children): self
    {
        throw new \LogicException('not yet implemented');
    }
}
