<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Tests\Internal\Tokenizer;

use Akankov\HtmlAst\Internal\Tokenizer\Tokenizer;
use Akankov\HtmlAst\Node\AttributeQuoteStyle;
use Akankov\HtmlAst\Token\CdataToken;
use Akankov\HtmlAst\Token\CharacterToken;
use Akankov\HtmlAst\Token\CommentToken;
use Akankov\HtmlAst\Token\DoctypeToken;
use Akankov\HtmlAst\Token\EndOfFileToken;
use Akankov\HtmlAst\Token\EndTagToken;
use Akankov\HtmlAst\Token\StartTagToken;
use Akankov\HtmlAst\Token\WhitespaceToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Per-fixture token-detail assertions. Every fixture from
 * `tests/fixtures/tokenizer/` has a dedicated test here that asserts the
 * concrete token sequence. Round-trip fidelity is asserted separately by
 * {@see RoundTripTest}.
 *
 * Pattern note: `if (!$x instanceof Y) self::fail()` is used in lieu of
 * `assertInstanceOf` because Phan does not narrow types through PHPUnit's
 * `assertInstanceOf` while PHPStan does — the `if`-fail pattern narrows
 * for both analyzers without duplication.
 */
#[CoversClass(Tokenizer::class)]
final class TokenizerTest extends TestCase
{
    private Tokenizer $tokenizer;

    protected function setUp(): void
    {
        $this->tokenizer = new Tokenizer();
    }

    public function testEmptyInputProducesOnlyEof(): void
    {
        $stream = $this->tokenizer->tokenize('');

        self::assertCount(1, $stream->tokens);
        $eof = $stream->tokens[0];
        if (! $eof instanceof EndOfFileToken) {
            self::fail('Expected EndOfFileToken at position 0');
        }
        self::assertSame(0, $eof->range->start);
        self::assertSame(0, $eof->range->end);
    }

    public function testSimpleTextElement(): void
    {
        $stream = $this->tokenizer->tokenize('<p>hello</p>');

        self::assertCount(4, $stream->tokens);

        $start = $stream->tokens[0];
        if (! $start instanceof StartTagToken) {
            self::fail('Expected StartTagToken at position 0');
        }
        self::assertSame('p', $start->tagName);
        self::assertSame('<p>', $start->raw);

        $text = $stream->tokens[1];
        if (! $text instanceof CharacterToken) {
            self::fail('Expected CharacterToken at position 1');
        }
        self::assertSame('hello', $text->data);

        $end = $stream->tokens[2];
        if (! $end instanceof EndTagToken) {
            self::fail('Expected EndTagToken at position 2');
        }
        self::assertSame('p', $end->tagName);

        self::assertInstanceOf(EndOfFileToken::class, $stream->tokens[3]);
    }

    public function testFourAttributeQuoteStyles(): void
    {
        $stream = $this->tokenizer->tokenize('<a href="x" class=\'y\' disabled>z</a>');

        $start = $stream->tokens[0];
        if (! $start instanceof StartTagToken) {
            self::fail('Expected StartTagToken at position 0');
        }
        self::assertSame('a', $start->tagName);
        self::assertCount(3, $start->attributes);

        self::assertSame('href', $start->attributes[0]->name);
        self::assertSame('x', $start->attributes[0]->value);
        self::assertSame(AttributeQuoteStyle::Double, $start->attributes[0]->quoteStyle);

        self::assertSame('class', $start->attributes[1]->name);
        self::assertSame('y', $start->attributes[1]->value);
        self::assertSame(AttributeQuoteStyle::Single, $start->attributes[1]->quoteStyle);

        self::assertSame('disabled', $start->attributes[2]->name);
        self::assertSame('', $start->attributes[2]->value);
        self::assertSame(AttributeQuoteStyle::Empty, $start->attributes[2]->quoteStyle);
    }

    public function testComment(): void
    {
        $stream = $this->tokenizer->tokenize('<!-- a -->');

        $comment = $stream->tokens[0];
        if (! $comment instanceof CommentToken) {
            self::fail('Expected CommentToken at position 0');
        }
        self::assertSame(' a ', $comment->data);
        self::assertSame('<!-- a -->', $comment->raw);
    }

    public function testDoctype(): void
    {
        $stream = $this->tokenizer->tokenize('<!DOCTYPE html>');

        $token = $stream->tokens[0];
        if (! $token instanceof DoctypeToken) {
            self::fail('Expected DoctypeToken at position 0');
        }
        self::assertSame('html', $token->name);
        self::assertNull($token->publicId);
        self::assertNull($token->systemId);
        self::assertFalse($token->forceQuirks);
    }

    public function testCdataSection(): void
    {
        $stream = $this->tokenizer->tokenize('<svg><![CDATA[ x ]]></svg>');

        // svg start, cdata, svg end, EOF
        self::assertCount(4, $stream->tokens);

        $start = $stream->tokens[0];
        if (! $start instanceof StartTagToken) {
            self::fail('Expected StartTagToken at position 0');
        }
        self::assertSame('svg', $start->tagName);

        $cdata = $stream->tokens[1];
        if (! $cdata instanceof CdataToken) {
            self::fail('Expected CdataToken at position 1');
        }
        self::assertSame(' x ', $cdata->data);
        self::assertSame('<![CDATA[ x ]]>', $cdata->raw);

        self::assertInstanceOf(EndTagToken::class, $stream->tokens[2]);
    }

    public function testScriptDataDoesNotTokenizeNestedTags(): void
    {
        $stream = $this->tokenizer->tokenize("<script>var x = '<a>';</script>");

        $start = $stream->tokens[0];
        if (! $start instanceof StartTagToken) {
            self::fail('Expected StartTagToken at position 0');
        }
        self::assertSame('script', $start->tagName);

        // The <a> inside script must NOT have been tokenized as a tag.
        $hasInnerATag = false;
        foreach ($stream->tokens as $t) {
            if ($t instanceof StartTagToken && $t->tagName === 'a') {
                $hasInnerATag = true;
                break;
            }
        }
        self::assertFalse($hasInnerATag, 'ScriptData state should not tokenize nested tags');

        // The closing </script> is recognized.
        $hasScriptEnd = false;
        foreach ($stream->tokens as $t) {
            if ($t instanceof EndTagToken && $t->tagName === 'script') {
                $hasScriptEnd = true;
                break;
            }
        }
        self::assertTrue($hasScriptEnd);
    }

    public function testNamedAndNumericEntities(): void
    {
        $stream = $this->tokenizer->tokenize('&amp;&#x41;&copy;');

        // The tokenizer batches consecutive character runs into a single
        // CharacterToken — round-trip fidelity is preserved by `$raw`,
        // and the decoded data is concatenated.
        $charTokens = [];
        foreach ($stream->tokens as $token) {
            if ($token instanceof CharacterToken) {
                $charTokens[] = $token;
            }
        }

        self::assertCount(1, $charTokens);
        self::assertSame('&amp;&#x41;&copy;', $charTokens[0]->raw);
        self::assertSame("&A\u{00A9}", $charTokens[0]->data);
    }

    public function testMalformedTagPreservesContent(): void
    {
        // Per WHATWG TagName state, '<' is just appended to the tag name.
        // So `<p<>` becomes a single start tag with tagName="p<".
        $stream = $this->tokenizer->tokenize('<p<>');

        $start = $stream->tokens[0];
        if (! $start instanceof StartTagToken) {
            self::fail('Expected StartTagToken at position 0');
        }
        self::assertSame('p<', $start->tagName);
        self::assertSame('<p<>', $start->raw);
    }

    public function testSvgWithSelfClosing(): void
    {
        $stream = $this->tokenizer->tokenize('<svg viewBox="0 0 10 10"><circle cx="5"/></svg>');

        $svg = $stream->tokens[0];
        if (! $svg instanceof StartTagToken) {
            self::fail('Expected StartTagToken at position 0');
        }
        self::assertSame('svg', $svg->tagName);

        // Tokenizer lowercases all attribute names per WHATWG §13.2.5.32.
        // SVG case-correction (viewBox, gradientUnits, etc.) happens at
        // tree-construction time (M1.B), not in the tokenizer. The raw
        // bytes are preserved for round-trip; only the canonical name
        // lookup is lowercased.
        self::assertSame('viewbox', $svg->attributes[0]->name);

        $circle = $stream->tokens[1];
        if (! $circle instanceof StartTagToken) {
            self::fail('Expected StartTagToken at position 1');
        }
        self::assertSame('circle', $circle->tagName);
        self::assertTrue($circle->selfClosing);

        $svgEnd = $stream->tokens[2];
        if (! $svgEnd instanceof EndTagToken) {
            self::fail('Expected EndTagToken at position 2');
        }
        self::assertSame('svg', $svgEnd->tagName);
    }

    public function testWhitespaceOnlyRunBecomesWhitespaceToken(): void
    {
        $stream = $this->tokenizer->tokenize('<p>   </p>');

        self::assertInstanceOf(StartTagToken::class, $stream->tokens[0]);

        $ws = $stream->tokens[1];
        if (! $ws instanceof WhitespaceToken) {
            self::fail('Expected WhitespaceToken at position 1');
        }
        self::assertSame('   ', $ws->raw);

        self::assertInstanceOf(EndTagToken::class, $stream->tokens[2]);
    }
}
