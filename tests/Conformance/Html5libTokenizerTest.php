<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Tests\Conformance;

use Akankov\HtmlAst\Internal\Tokenizer\Tokenizer;
use Akankov\HtmlAst\Token\CdataToken;
use Akankov\HtmlAst\Token\CharacterToken;
use Akankov\HtmlAst\Token\CommentToken;
use Akankov\HtmlAst\Token\DoctypeToken;
use Akankov\HtmlAst\Token\EndOfFileToken;
use Akankov\HtmlAst\Token\EndTagToken;
use Akankov\HtmlAst\Token\StartTagToken;
use Akankov\HtmlAst\Token\Token;
use Akankov\HtmlAst\Token\WhitespaceToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * html5lib-tests tokenizer conformance (M1.B part 2).
 *
 * Runs every vendored suite from `tests/fixtures/html5lib-tokenizer/` (see
 * `bin/fetch-html5lib-tests.php`) whose case starts in the Data state. Token
 * sequences are compared; parse-error codes are NOT compared yet — that is
 * the error-catalog follow-up, tracked in PLAN.md M1.B.
 *
 * Out of scope by construction:
 * - cases whose `initialStates` exclude "Data state" (the public tokenizer
 *   API always starts in Data; RCDATA/RAWTEXT/PLAINTEXT/CDATA entry points
 *   arrive with the tree-construction stage in M1.C),
 * - cases with `lastStartTag` (same reason),
 * - `xmlViolation.test` (an XML-coercion serialization contract this
 *   package does not implement).
 */
#[CoversClass(Tokenizer::class)]
final class Html5libTokenizerTest extends TestCase
{
    private const string FIXTURE_DIR = __DIR__ . '/../fixtures/html5lib-tokenizer';

    private const array SUITE_FILES = [
        'test1.test',
        'test2.test',
        'test3.test',
        'test4.test',
        'contentModelFlags.test',
        'domjs.test',
        'entities.test',
        'escapeFlag.test',
        'namedEntities.test',
        'numericEntities.test',
        'pendingSpecChanges.test',
        'unicodeChars.test',
        'unicodeCharsProblematic.test',
    ];

    private const string REASON_CR = 'CR/CRLF → LF input normalization not yet implemented (preprocessing layer, lands with M1.C)';

    private const string REASON_NUL = 'per-state NUL → U+FFFD replacement not yet implemented (lands with M1.C)';

    /**
     * Known divergences, keyed like the provider (`"file#index description"`).
     * Every entry needs a reason; shrinking this list is the M1.C follow-up.
     * 39 entries / 6,690 runnable cases at the time of writing.
     *
     * @var array<string, string>
     */
    private const array SKIP = [
        'domjs.test#0 CR in bogus comment state' => self::REASON_CR,
        'domjs.test#1 CRLF in bogus comment state' => self::REASON_CR,
        'domjs.test#2 CRLFLF in bogus comment state' => self::REASON_CR,
        'domjs.test#33 --!NUL in comment ' => self::REASON_NUL,
        'test3.test#76 <!\u0000' => self::REASON_NUL,
        'test3.test#82 <! \u0000' => self::REASON_NUL,
        'test3.test#89 <!--\u0000' => self::REASON_NUL,
        'test3.test#95 <!-- \u0000' => self::REASON_NUL,
        'test3.test#107 <!-- -\u0000' => self::REASON_NUL,
        'test3.test#167 <!---\u0000' => self::REASON_NUL,
        'test3.test#179 <!----\u0000' => self::REASON_NUL,
        'test3.test#194 <!----!CR>' => self::REASON_CR,
        'test3.test#195 <!----!CRLF>' => self::REASON_CR,
        'test3.test#281 <!DOCTYPE\u0000' => self::REASON_NUL,
        'test3.test#290 <!DOCTYPE \u0000' => self::REASON_NUL,
        'test3.test#320 <!DOCTYPE a\u0000' => self::REASON_NUL,
        'test3.test#727 <!DOCTYPEa\u0000' => self::REASON_NUL,
        'test3.test#1119 </\u0000' => self::REASON_NUL,
        'test3.test#1125 </ \u0000' => self::REASON_NUL,
        'test3.test#1159 <?\u0000' => self::REASON_NUL,
        'test3.test#1165 <? \u0000' => self::REASON_NUL,
        'test3.test#1199 <a\u0000>' => self::REASON_NUL,
        'test3.test#1208 <a \u0000>' => self::REASON_NUL,
        'test3.test#1241 <a a\u0000>' => self::REASON_NUL,
        'test3.test#1250 <a a \u0000>' => self::REASON_NUL,
        'test3.test#1302 <a a=\u0000>' => self::REASON_NUL,
        'test3.test#1313 <a a="\u0000">' => self::REASON_NUL,
        'test3.test#1350 <a a=\'\u0000\'>' => self::REASON_NUL,
        'test3.test#1361 <a a=\'\'\u0000>' => self::REASON_NUL,
        'test3.test#1435 <a a=a\u0000>' => self::REASON_NUL,
        'test3.test#1504 <a/\u0000>' => self::REASON_NUL,
        'test4.test#58 U+0000 in lookahead region' => self::REASON_NUL,
        'test4.test#62 CR followed by non-LF' => self::REASON_CR,
        'test4.test#63 CR at EOF' => self::REASON_CR,
        'test4.test#65 CR LF' => self::REASON_CR,
        'test4.test#66 CR CR' => self::REASON_CR,
        'test4.test#68 LF CR' => self::REASON_CR,
        'test4.test#69 text CR CR CR text' => self::REASON_CR,
        'unicodeCharsProblematic.test#4 CR followed by U+0000' => self::REASON_CR,
    ];

    /**
     * @param list<mixed> $expected
     */
    #[DataProvider('provideHtml5libCases')]
    public function testTokenizerConformance(string $input, array $expected): void
    {
        $stream = (new Tokenizer())->tokenize($input);

        self::assertEquals(
            self::normalizeExpected($expected),
            self::normalizeActual($stream->tokens),
        );
    }

    /**
     * @return iterable<string, array{string, list<mixed>}>
     */
    public static function provideHtml5libCases(): iterable
    {
        foreach (self::SUITE_FILES as $file) {
            $raw = file_get_contents(self::FIXTURE_DIR . '/' . $file);
            if ($raw === false) {
                throw new \RuntimeException("missing fixture {$file}; run bin/fetch-html5lib-tests.php");
            }

            /** @var array{tests: list<array<string, mixed>>} $suite */
            $suite = json_decode($raw, true, 32, \JSON_THROW_ON_ERROR);

            foreach ($suite['tests'] as $i => $test) {
                $description = $test['description'] ?? 'unnamed';
                \assert(\is_string($description));
                // Index keeps keys unique (descriptions repeat within a
                // suite); fixtures are committed, so indices are stable.
                $key = "{$file}#{$i} {$description}";

                if (isset(self::SKIP[$key])) {
                    continue;
                }

                if (isset($test['lastStartTag'])) {
                    continue;
                }

                $states = $test['initialStates'] ?? ['Data state'];
                \assert(\is_array($states));
                if (!\in_array('Data state', $states, true)) {
                    continue;
                }

                $input = $test['input'];
                \assert(\is_string($input));
                $output = $test['output'];
                \assert(\is_array($output));
                $output = array_values($output);

                if ($test['doubleEscaped'] ?? false) {
                    $input = self::jsonUnescape($input);
                    $output = self::unescapeOutput($output);
                }

                yield $key => [$input, $output];
            }
        }
    }

    /**
     * Normalize html5lib expectation tuples: coalesce adjacent Character
     * tuples, expand DOCTYPE "correctness" into forceQuirks, normalize the
     * optional self-closing flag.
     *
     * @param list<mixed> $tuples
     *
     * @return list<array<int, mixed>>
     */
    private static function normalizeExpected(array $tuples): array
    {
        $result = [];
        $pendingCharacters = '';

        foreach ($tuples as $tuple) {
            \assert(\is_array($tuple));
            $kind = $tuple[0];
            if ($kind === 'Character') {
                \assert(\is_string($tuple[1]));
                $pendingCharacters .= $tuple[1];

                continue;
            }

            if ($pendingCharacters !== '') {
                $result[] = ['Character', $pendingCharacters];
                $pendingCharacters = '';
            }

            $result[] = match ($kind) {
                'StartTag' => ['StartTag', $tuple[1], $tuple[2], $tuple[3] ?? false],
                'EndTag'   => ['EndTag', $tuple[1]],
                'Comment'  => ['Comment', $tuple[1]],
                'DOCTYPE'  => ['DOCTYPE', $tuple[1], $tuple[2], $tuple[3], !($tuple[4] ?? false)],
                default    => throw new \UnexpectedValueException('unknown tuple kind ' . var_export($kind, true)),
            };
        }

        if ($pendingCharacters !== '') {
            $result[] = ['Character', $pendingCharacters];
        }

        return $result;
    }

    /**
     * Normalize our token stream to the same tuple shape: drop EOF, coalesce
     * Character/Whitespace runs, first-attribute-wins maps.
     *
     * @param list<Token> $tokens
     *
     * @return list<array<int, mixed>>
     */
    private static function normalizeActual(array $tokens): array
    {
        $result = [];
        $pendingCharacters = '';

        foreach ($tokens as $token) {
            if ($token instanceof EndOfFileToken) {
                continue;
            }

            if ($token instanceof CharacterToken) {
                $pendingCharacters .= $token->data;

                continue;
            }

            if ($token instanceof WhitespaceToken) {
                $pendingCharacters .= $token->data;

                continue;
            }

            if ($pendingCharacters !== '') {
                $result[] = ['Character', $pendingCharacters];
                $pendingCharacters = '';
            }

            if ($token instanceof StartTagToken) {
                $attributes = [];
                foreach ($token->attributes as $attribute) {
                    // Spec: the first occurrence of a duplicated attribute wins.
                    $attributes[$attribute->name] ??= $attribute->value;
                }
                $result[] = ['StartTag', $token->tagName, $attributes, $token->selfClosing];
            } elseif ($token instanceof EndTagToken) {
                $result[] = ['EndTag', $token->tagName];
            } elseif ($token instanceof CommentToken) {
                $result[] = ['Comment', $token->data];
            } elseif ($token instanceof DoctypeToken) {
                $result[] = ['DOCTYPE', $token->name, $token->publicId, $token->systemId, $token->forceQuirks];
            } elseif ($token instanceof CdataToken) {
                // In the Data state "<![CDATA[…]]>" is spec'd as a bogus
                // comment whose data is "[CDATA[…]]".
                $result[] = ['Comment', '[CDATA[' . $token->data . ']]'];
            } else {
                throw new \UnexpectedValueException('unhandled token ' . $token::class);
            }
        }

        if ($pendingCharacters !== '') {
            $result[] = ['Character', $pendingCharacters];
        }

        return $result;
    }

    /**
     * html5lib `doubleEscaped` cases store strings with literal \uXXXX
     * escapes. Not decodable via json_decode: the whole point of double
     * escaping upstream is smuggling lone surrogates, which JSON rejects.
     * Decode UTF-16 escapes by hand — pairs combine to astral codepoints,
     * lone surrogates become WTF-8 bytes so the tokenizer still receives a
     * byte stream.
     */
    private static function jsonUnescape(string $value): string
    {
        $value = (string) preg_replace_callback(
            '/\\\\u(d[89ab][0-9a-f]{2})\\\\u(d[c-f][0-9a-f]{2})/i',
            static function (array $match): string {
                $high = (int) hexdec($match[1]);
                $low = (int) hexdec($match[2]);

                return self::utf8Chr(0x10000 + (($high - 0xD800) << 10) + ($low - 0xDC00));
            },
            $value,
        );

        return (string) preg_replace_callback(
            '/\\\\u([0-9a-f]{4})/i',
            static fn (array $match): string => self::utf8Chr((int) hexdec($match[1])),
            $value,
        );
    }

    private static function utf8Chr(int $codepoint): string
    {
        if ($codepoint < 0x80) {
            return \chr($codepoint);
        }

        if ($codepoint < 0x800) {
            return \chr(0xC0 | ($codepoint >> 6)) . \chr(0x80 | ($codepoint & 0x3F));
        }

        if ($codepoint < 0x10000) {
            return \chr(0xE0 | ($codepoint >> 12))
                . \chr(0x80 | (($codepoint >> 6) & 0x3F))
                . \chr(0x80 | ($codepoint & 0x3F));
        }

        return \chr(0xF0 | ($codepoint >> 18))
            . \chr(0x80 | (($codepoint >> 12) & 0x3F))
            . \chr(0x80 | (($codepoint >> 6) & 0x3F))
            . \chr(0x80 | ($codepoint & 0x3F));
    }

    /**
     * @param list<mixed> $output
     *
     * @return list<mixed>
     */
    private static function unescapeOutput(array $output): array
    {
        $unescaped = [];
        foreach ($output as $tuple) {
            \assert(\is_array($tuple));
            $unescaped[] = array_map(
                static fn (mixed $field): mixed => \is_string($field) ? self::jsonUnescape($field) : $field,
                $tuple,
            );
        }

        return $unescaped;
    }
}
