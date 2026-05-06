<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Visitor;

use Akankov\HtmlAst\Node\Node;

/**
 * The visitor interface — the highest-leverage public API in the package, since
 * downstream linters, formatters, and minifiers will write more visitor
 * methods than they will ever call directly.
 *
 * Per PLAN §4 O5 lean (b), the protocol mirrors `nikic/php-parser`'s
 * battle-tested `enterNode` / `leaveNode` pair. The exact return-type contract
 * is the **permissive** variant — `null` is accepted as a synonym for
 * {@see VisitorAction::Continue} so that visitors which only act on a few
 * node kinds can early-return without ceremony.
 *
 * Method semantics:
 *
 * - `enterNode($node)` runs *before* descending into the node's children.
 *   Returning {@see VisitorAction::SkipChildren} prevents the descent.
 * - `leaveNode($node)` runs *after* the children have been visited. The
 *   children visible to `leaveNode` already reflect any structural changes
 *   from descendant visitors.
 *
 * Return-value contract:
 *
 * - `null` or {@see VisitorAction::Continue} — keep the node, descend normally.
 * - A {@see Node} instance — replace the current node with that one.
 * - {@see VisitorAction::Remove} — delete the current node from its parent.
 * - {@see VisitorAction::SkipChildren} — keep the node, do not recurse (enter only).
 * - {@see VisitorAction::Stop} — end the entire traversal.
 */
interface Visitor
{
    public function enterNode(Node $node): Node|VisitorAction|null;

    public function leaveNode(Node $node): Node|VisitorAction|null;
}
