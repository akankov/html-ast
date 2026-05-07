<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Tests\Internal\Tokenizer;

use Akankov\HtmlAst\Internal\Tokenizer\Tokenizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The headline acceptance criterion for M1.A: for every fixture, concat of
 * every token's `$raw` byte-for-byte equals the original input.
 *
 * Round-trip fidelity is the differentiator (PLAN §4 O4) — without it the
 * package has no reason to exist as a separate library from
 * `\Dom\HTMLDocument`. This test catches almost every tokenizer bug because
 * any state that drops, duplicates, or reorders bytes shows up as an
 * inequality immediately.
 */
#[CoversClass(Tokenizer::class)]
final class RoundTripTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function fixtures(): iterable
    {
        $dir = __DIR__ . '/../../fixtures/tokenizer';
        $files = glob($dir . '/*.html');
        \assert(\is_array($files));

        foreach ($files as $file) {
            $name = basename($file);
            $content = file_get_contents($file);
            \assert(\is_string($content));
            yield $name => [$name, $content];
        }
    }

    #[DataProvider('fixtures')]
    public function testRoundTrip(string $name, string $input): void
    {
        $tokenizer = new Tokenizer();
        $stream = $tokenizer->tokenize($input);

        $reassembled = '';
        foreach ($stream->tokens as $token) {
            $reassembled .= $token->raw;
        }

        self::assertSame(
            $input,
            $reassembled,
            "Round-trip fidelity broken for fixture {$name}: tokens do not concatenate back to the input.",
        );
    }
}
