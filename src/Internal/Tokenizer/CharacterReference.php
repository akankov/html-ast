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
        // Longest match by probing candidate lengths downward. Bounded by the
        // longest table key (33 bytes), this is ≤31 hash lookups per `&` —
        // unlike a table scan, it stays O(1) in the table size, which matters
        // now that the table holds the full ~2200-entry WHATWG list.
        $longest = min(NamedCharacterReferences::MAX_LENGTH, \strlen($input) - $startOffset);

        for ($length = $longest; $length >= NamedCharacterReferences::MIN_LENGTH; --$length) {
            $candidate = substr($input, $startOffset, $length);
            if (isset(NamedCharacterReferences::TABLE[$candidate])) {
                return ['consumed' => $length, 'decoded' => NamedCharacterReferences::TABLE[$candidate]];
            }
        }

        return null;
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
