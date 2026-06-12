<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Tests\Internal\Tokenizer;

use Akankov\HtmlAst\Internal\Tokenizer\CharacterReference;
use Akankov\HtmlAst\Internal\Tokenizer\NamedCharacterReferences;
use Akankov\HtmlAst\Internal\Tokenizer\Tokenizer;
use Akankov\HtmlAst\Internal\Tokenizer\TokenizerError;
use Akankov\HtmlAst\Token\CharacterToken;
use Akankov\HtmlAst\Token\StartTagToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * M1.B: the full WHATWG named-character-reference table and the spec rules
 * that only matter once the table is complete — longest-match against legacy
 * (semicolon-less) names, the historical attribute-value exception
 * (§13.2.5.73), and the missing-semicolon parse error.
 *
 * @phan-file-suppress PhanAccessClassConstantInternal, PhanAccessMethodInternal, PhanAccessPropertyInternal
 *     This test exercises `Internal\` classes directly — legitimate from the
 *     package's own suite; Phan has no notion of "tests belong to the package".
 * @phan-file-suppress PhanTypeInvalidDimOffset
 *     Phan caps array-shape inference on the 2231-entry TABLE constant, so
 *     keys outside its sample look like invalid offsets.
 */
#[CoversClass(CharacterReference::class)]
#[CoversClass(NamedCharacterReferences::class)]
#[CoversClass(Tokenizer::class)]
final class CharacterReferenceTest extends TestCase
{
    public function testGeneratedTableMatchesTheVendoredWhatwgData(): void
    {
        $json = file_get_contents(__DIR__ . '/../../../resources/entities.json');
        self::assertNotFalse($json);

        /** @var array<string, array{codepoints: list<int>, characters: string}> $expected */
        $expected = json_decode($json, true, 8, \JSON_THROW_ON_ERROR);

        self::assertCount(\count($expected), NamedCharacterReferences::TABLE);
        foreach ($expected as $name => $entry) {
            self::assertArrayHasKey($name, NamedCharacterReferences::TABLE);
            self::assertSame($entry['characters'], NamedCharacterReferences::TABLE[$name], $name);
        }
    }

    public function testLengthBoundsMatchTheTable(): void
    {
        $lengths = array_map(\strlen(...), array_keys(NamedCharacterReferences::TABLE));

        self::assertSame(NamedCharacterReferences::MIN_LENGTH, min($lengths));
        self::assertSame(NamedCharacterReferences::MAX_LENGTH, max($lengths));
    }

    public function testMultiCodepointAndLigatureEntriesDecode(): void
    {
        self::assertSame("\u{2242}\u{0338}", NamedCharacterReferences::TABLE['&NotEqualTilde;']);
        self::assertSame('fj', NamedCharacterReferences::TABLE['&fjlig;']);
        self::assertSame("\u{2233}", NamedCharacterReferences::TABLE['&CounterClockwiseContourIntegral;']);
    }

    public function testLongestMatchWinsOverLegacyPrefix(): void
    {
        $full = CharacterReference::matchNamed('&notin;', 0);
        self::assertNotNull($full);
        self::assertSame(7, $full['consumed']);
        self::assertSame("\u{2209}", $full['decoded']);

        // '&notit;' has no entry, but the legacy '&not' prefix matches.
        $legacy = CharacterReference::matchNamed('&notit;', 0);
        self::assertNotNull($legacy);
        self::assertSame(4, $legacy['consumed']);
        self::assertSame("\u{00AC}", $legacy['decoded']);
    }

    public function testUnknownNameDoesNotMatch(): void
    {
        self::assertNull(CharacterReference::matchNamed('&xyzzy;', 0));
    }

    public function testFullTableEntityDecodesInCharacterData(): void
    {
        $stream = (new Tokenizer())->tokenize('&hopf;');

        $text = $stream->tokens[0];
        if (! $text instanceof CharacterToken) {
            self::fail('Expected CharacterToken at position 0');
        }
        self::assertSame(NamedCharacterReferences::TABLE['&hopf;'], $text->data);
        self::assertSame('&hopf;', $text->raw);
    }

    public function testLegacyEntityBeforeEqualsInAttributeStaysLiteral(): void
    {
        // WHATWG §13.2.5.73 historical exception: `?x=1&copy=2` in an
        // attribute value must keep `&copy` literal (no decode, no error).
        $tokenizer = new Tokenizer();
        $stream = $tokenizer->tokenize('<a href="?x=1&copy=2"></a>');

        $tag = $stream->tokens[0];
        if (! $tag instanceof StartTagToken) {
            self::fail('Expected StartTagToken at position 0');
        }
        self::assertSame('?x=1&copy=2', $tag->attributes[0]->value);
        self::assertSame([], $tokenizer->errors());
    }

    public function testLegacyEntityInAttributeDecodesWhenNotAmbiguous(): void
    {
        $tokenizer = new Tokenizer();
        $stream = $tokenizer->tokenize('<a href="x&copy!"></a>');

        $tag = $stream->tokens[0];
        if (! $tag instanceof StartTagToken) {
            self::fail('Expected StartTagToken at position 0');
        }
        self::assertSame("x\u{00A9}!", $tag->attributes[0]->value);
        self::assertContains(
            'missing-semicolon-after-character-reference',
            array_map(static fn (TokenizerError $error): string => $error->code, $tokenizer->errors()),
        );
    }

    public function testLegacyEntityInCharacterDataDecodesWithMissingSemicolonError(): void
    {
        $tokenizer = new Tokenizer();
        $stream = $tokenizer->tokenize('x&copy!');

        $text = $stream->tokens[0];
        if (! $text instanceof CharacterToken) {
            self::fail('Expected CharacterToken at position 0');
        }
        self::assertSame("x\u{00A9}!", $text->data);
        self::assertSame('x&copy!', $text->raw);
        self::assertContains(
            'missing-semicolon-after-character-reference',
            array_map(static fn (TokenizerError $error): string => $error->code, $tokenizer->errors()),
        );
    }
}
