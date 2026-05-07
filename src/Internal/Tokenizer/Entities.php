<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Internal\Tokenizer;

/**
 * Named character reference table.
 *
 * **M1.A scope:** seed table covering ~30 commonly-used entities. The full
 * WHATWG spec table has ~2200 entries; the rest will be backfilled from the
 * canonical `entities.json` in M1.B (see CREDITS.md). Round-trip fidelity
 * does **not** depend on this table being complete — unrecognized references
 * are emitted as literal `CharacterToken`s with their original source bytes
 * intact, exactly as the spec mandates.
 *
 * Some entries omit the trailing semicolon — the WHATWG list calls these
 * legacy named references; matching is "longest match" so `&AMP` on its own
 * resolves but `&AMPx` does not.
 *
 * Sourced from the WHATWG named-character-reference list (Public Domain
 * spec data, MIT-compatible).
 *
 * @internal no BC guarantees
 */
final class Entities
{
    /**
     * @return array<string, string> name (with leading & and trailing ;) → decoded UTF-8 value
     */
    public static function table(): array
    {
        return [
            // Fundamentals — XML's "predefined entities"
            '&amp;' => '&',
            '&AMP;' => '&',
            '&AMP' => '&',
            '&amp' => '&',
            '&lt;' => '<',
            '&LT;' => '<',
            '&LT' => '<',
            '&lt' => '<',
            '&gt;' => '>',
            '&GT;' => '>',
            '&GT' => '>',
            '&gt' => '>',
            '&quot;' => '"',
            '&QUOT;' => '"',
            '&QUOT' => '"',
            '&quot' => '"',
            '&apos;' => "'",
            // Common typography
            '&copy;' => "\u{00A9}",
            '&COPY;' => "\u{00A9}",
            '&copy' => "\u{00A9}",
            '&COPY' => "\u{00A9}",
            '&reg;' => "\u{00AE}",
            '&REG;' => "\u{00AE}",
            '&reg' => "\u{00AE}",
            '&REG' => "\u{00AE}",
            '&trade;' => "\u{2122}",
            '&TRADE;' => "\u{2122}",
            '&nbsp;' => "\u{00A0}",
            '&nbsp' => "\u{00A0}",
            '&mdash;' => "\u{2014}",
            '&ndash;' => "\u{2013}",
            '&hellip;' => "\u{2026}",
            '&laquo;' => "\u{00AB}",
            '&raquo;' => "\u{00BB}",
            '&ldquo;' => "\u{201C}",
            '&rdquo;' => "\u{201D}",
            '&lsquo;' => "\u{2018}",
            '&rsquo;' => "\u{2019}",
            // Math
            '&times;' => "\u{00D7}",
            '&divide;' => "\u{00F7}",
            '&plusmn;' => "\u{00B1}",
            '&deg;' => "\u{00B0}",
        ];
    }

    /**
     * Numeric-character-reference replacement table for the C1-control range.
     * WHATWG §13.2.5.80 mandates these specific replacements regardless of
     * what the spec'd codepoint would be.
     *
     * @return array<int, int> input codepoint → replacement codepoint
     */
    public static function numericReplacements(): array
    {
        return [
            0x00 => 0xFFFD,
            0x80 => 0x20AC,
            0x82 => 0x201A,
            0x83 => 0x0192,
            0x84 => 0x201E,
            0x85 => 0x2026,
            0x86 => 0x2020,
            0x87 => 0x2021,
            0x88 => 0x02C6,
            0x89 => 0x2030,
            0x8A => 0x0160,
            0x8B => 0x2039,
            0x8C => 0x0152,
            0x8E => 0x017D,
            0x91 => 0x2018,
            0x92 => 0x2019,
            0x93 => 0x201C,
            0x94 => 0x201D,
            0x95 => 0x2022,
            0x96 => 0x2013,
            0x97 => 0x2014,
            0x98 => 0x02DC,
            0x99 => 0x2122,
            0x9A => 0x0161,
            0x9B => 0x203A,
            0x9C => 0x0153,
            0x9E => 0x017E,
            0x9F => 0x0178,
        ];
    }
}
