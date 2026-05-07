<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Internal\Tokenizer;

/**
 * The five WHATWG ASCII predicates the tokenizer needs in dozens of states.
 * Single-codepoint single-byte string inputs only — the tokenizer always
 * checks one consumed character at a time.
 *
 * @internal no BC guarantees
 */
final class AsciiPredicates
{
    /**
     * Per WHATWG: U+0009 TAB, U+000A LF, U+000C FF, U+000D CR, U+0020 SPACE.
     * Excludes U+000B VT (which is *not* HTML whitespace despite being a
     * common "whitespace" predicate elsewhere).
     */
    public static function isWhitespace(string $c): bool
    {
        return $c === "\t" || $c === "\n" || $c === "\f" || $c === "\r" || $c === ' ';
    }

    public static function isAsciiAlpha(string $c): bool
    {
        if ($c === '') {
            return false;
        }
        $o = \ord($c);

        return ($o >= 0x41 && $o <= 0x5A) || ($o >= 0x61 && $o <= 0x7A);
    }

    public static function isAsciiUpperAlpha(string $c): bool
    {
        if ($c === '') {
            return false;
        }
        $o = \ord($c);

        return $o >= 0x41 && $o <= 0x5A;
    }

    public static function isAsciiAlphanumeric(string $c): bool
    {
        if ($c === '') {
            return false;
        }
        $o = \ord($c);

        return ($o >= 0x30 && $o <= 0x39)
            || ($o >= 0x41 && $o <= 0x5A)
            || ($o >= 0x61 && $o <= 0x7A);
    }

    public static function isAsciiDigit(string $c): bool
    {
        if ($c === '') {
            return false;
        }
        $o = \ord($c);

        return $o >= 0x30 && $o <= 0x39;
    }

    public static function isAsciiHexDigit(string $c): bool
    {
        if ($c === '') {
            return false;
        }
        $o = \ord($c);

        return ($o >= 0x30 && $o <= 0x39)
            || ($o >= 0x41 && $o <= 0x46)
            || ($o >= 0x61 && $o <= 0x66);
    }
}
