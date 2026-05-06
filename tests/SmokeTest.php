<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class SmokeTest extends TestCase
{
    /**
     * Every public class declared in the package must be autoloadable.
     *
     * Until M1+ implementations land, the smoke test is the only thing keeping
     * CI green. It exists specifically to catch namespace typos, missing files,
     * and circular requires that would otherwise only surface when a real
     * consumer tried to instantiate the API.
     *
     * @return iterable<string, array{0: class-string}>
     */
    public static function publicClasses(): iterable
    {
        $classes = [
            // Position
            \Akankov\HtmlAst\Position\ByteRange::class,
            \Akankov\HtmlAst\Position\SourceMap::class,

            // Token
            \Akankov\HtmlAst\Token\Token::class,
            \Akankov\HtmlAst\Token\TokenStream::class,
            \Akankov\HtmlAst\Token\TokenKind::class,

            // Node
            \Akankov\HtmlAst\Node\Node::class,
            \Akankov\HtmlAst\Node\NodeKind::class,
            \Akankov\HtmlAst\Node\AttributeQuoteStyle::class,
            \Akankov\HtmlAst\Node\QuirksMode::class,
            \Akankov\HtmlAst\Node\Document::class,
            \Akankov\HtmlAst\Node\DocumentFragment::class,
            \Akankov\HtmlAst\Node\Element::class,
            \Akankov\HtmlAst\Node\Attribute::class,
            \Akankov\HtmlAst\Node\Text::class,
            \Akankov\HtmlAst\Node\Comment::class,
            \Akankov\HtmlAst\Node\Doctype::class,
            \Akankov\HtmlAst\Node\ProcessingInstruction::class,
            \Akankov\HtmlAst\Node\CDataSection::class,

            // Visitor
            \Akankov\HtmlAst\Visitor\Visitor::class,
            \Akankov\HtmlAst\Visitor\NodeTraverser::class,
            \Akankov\HtmlAst\Visitor\VisitorAction::class,

            // Parser
            \Akankov\HtmlAst\Parser\Parser::class,
            \Akankov\HtmlAst\Parser\ParseMode::class,
            \Akankov\HtmlAst\Parser\ParseOptions::class,
            \Akankov\HtmlAst\Parser\ParseResult::class,
            \Akankov\HtmlAst\Parser\ParseError::class,
            \Akankov\HtmlAst\Parser\ParseErrorKind::class,
            \Akankov\HtmlAst\Parser\NativeParser::class,
            \Akankov\HtmlAst\Parser\MastermindsParser::class,

            // Printer
            \Akankov\HtmlAst\Printer\Printer::class,
            \Akankov\HtmlAst\Printer\StandardPrinter::class,

            // Contract
            \Akankov\HtmlAst\Contract\ParserContract::class,
            \Akankov\HtmlAst\Contract\VisitorContract::class,
            \Akankov\HtmlAst\Contract\PrinterContract::class,
        ];

        foreach ($classes as $class) {
            yield $class => [$class];
        }
    }

    #[DataProvider('publicClasses')]
    public function testPublicClassIsLoadable(string $class): void
    {
        self::assertTrue(
            class_exists($class) || interface_exists($class) || enum_exists($class) || trait_exists($class),
            "Public type {$class} could not be autoloaded.",
        );
    }
}
