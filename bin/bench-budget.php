#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Performance-budget harness for `akankov/html-ast`.
 *
 * Per PLAN §7 risk register and the M0 design doc:
 *
 *   - Parse time: NativeParser must stay within 2× raw \Dom\HTMLDocument.
 *   - Memory:     NativeParser must stay within 3× raw \Dom\HTMLDocument.
 *
 * Once NativeParser exists (M1), this script will:
 *
 *   1. Walk every fixture in tests/fixtures/.
 *   2. For each fixture, parse it both ways (raw \Dom\HTMLDocument and
 *      NativeParser), recording wall time and peak memory.
 *   3. Report per-fixture and aggregate ratios; exit non-zero if any ratio
 *      exceeds the budget.
 *
 * The script is wired into CI (a nightly job) from M1 onward so budget
 * regressions block release.
 */

namespace Akankov\HtmlAst\Bench;

if (\PHP_VERSION_ID < 80400) {
    \fwrite(\STDERR, "bench-budget requires PHP 8.4+ (it benchmarks against \\Dom\\HTMLDocument).\n");
    exit(2);
}

if (! \class_exists(\Akankov\HtmlAst\Parser\NativeParser::class)) {
    \fwrite(\STDERR, "NativeParser not yet available — performance budget is not enforced until M1 lands.\n");
    \fwrite(\STDERR, "When M1 ships, this script will compare it against raw \\Dom\\HTMLDocument.\n");
    exit(0);
}

\fwrite(\STDERR, "TODO: implement bench-budget once NativeParser exists.\n");
exit(0);
