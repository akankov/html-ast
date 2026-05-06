<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Visitor;

/**
 * Control-flow sentinel returned by a {@see Visitor}'s `enterNode` /
 * `leaveNode` methods (PLAN §4 O5 lean (b) — nikic-style protocol with PHP-native
 * enums instead of integer constants).
 *
 * - {@see self::Continue}: traverse normally — the most common return.
 * - {@see self::SkipChildren}: visit this node, but do not recurse into its
 *   children. Only meaningful from `enterNode`.
 * - {@see self::Stop}: end the entire traversal immediately. Returned from any
 *   visitor method on any node aborts the traverser run.
 * - {@see self::Remove}: delete the current node from its parent. The parent's
 *   children list is filtered after `leaveNode` returns.
 *
 * To **replace** a node, return the replacement {@see \Akankov\HtmlAst\Node\Node}
 * instance directly from the visitor method instead of returning a
 * `VisitorAction`. Returning `null` is equivalent to {@see self::Continue}.
 */
enum VisitorAction
{
    case Continue;
    case SkipChildren;
    case Stop;
    case Remove;
}
