<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Internal\Tokenizer;

/**
 * Character-reference matcher. Operates on a substring view of the input
 * starting at the `&` and returns either a successful match (consumed
 * length + decoded UTF-8 value) or no-match.
 *
 * Per WHATWG §13.2.5.72–80, the matcher is "longest match" against the
 * named-reference table. Some references in the table do not require a
 * trailing semicolon (legacy compat), so matching `&AMPx` returns the
 * `&AMP` match (4 chars consumed, decoded `&`).
 *
 * @internal no BC guarantees
 */
final class CharacterReference
{
    /**
     * Try to match a named character reference. `$input` is the full source;
     * `$startOffset` points at the `&`.
     *
     * @return array{consumed: int, decoded: string}|null
     *                     consumed = number of bytes matched (including the `&`);
     *                     null = no named reference matched
     */
    public static function matchNamed(string $input, int $startOffset): ?array
    {
        $table = Entities::table();

        $bestMatchLen = 0;
        $bestDecoded = '';

        foreach ($table as $name => $decoded) {
            $nameLen = \strlen($name);
            if (substr($input, $startOffset, $nameLen) === $name && $nameLen > $bestMatchLen) {
                $bestMatchLen = $nameLen;
                $bestDecoded = $decoded;
            }
        }

        if ($bestMatchLen === 0) {
            return null;
        }

        return ['consumed' => $bestMatchLen, 'decoded' => $bestDecoded];
    }

    /**
     * Map a numeric codepoint to its UTF-8 form per WHATWG numeric-reference
     * end state (§13.2.5.80). Handles the C1-control replacement table,
     * surrogate exclusion, and the `0xFFFD` fallback.
     */
    public static function decodeNumeric(int $codepoint): string
    {
        $replacements = Entities::numericReplacements();
        if (isset($replacements[$codepoint])) {
            $codepoint = $replacements[$codepoint];
        }

        // Surrogates and out-of-range → replacement char per WHATWG
        if (
            $codepoint > 0x10FFFF
            || ($codepoint >= 0xD800 && $codepoint <= 0xDFFF)
        ) {
            $codepoint = 0xFFFD;
        }

        return self::utf8Encode($codepoint);
    }

    private static function utf8Encode(int $cp): string
    {
        if ($cp < 0x80) {
            return \chr($cp);
        }

        if ($cp < 0x800) {
            return \chr(0xC0 | ($cp >> 6))
                . \chr(0x80 | ($cp & 0x3F));
        }

        if ($cp < 0x10000) {
            return \chr(0xE0 | ($cp >> 12))
                . \chr(0x80 | (($cp >> 6) & 0x3F))
                . \chr(0x80 | ($cp & 0x3F));
        }

        return \chr(0xF0 | ($cp >> 18))
            . \chr(0x80 | (($cp >> 12) & 0x3F))
            . \chr(0x80 | (($cp >> 6) & 0x3F))
            . \chr(0x80 | ($cp & 0x3F));
    }
}
