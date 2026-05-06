<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Token;

/**
 * Discriminator for {@see Token}. The set is closed and matches the HTML5
 * tokenizer's emitted token kinds (WHATWG §13.2.5), with one addition:
 * {@see self::Whitespace} is used to preserve trivia tokens that the tree
 * builder would otherwise normalize away (PLAN §4 O4 lean (c)).
 */
enum TokenKind: string
{
    case Doctype = 'doctype';
    case StartTag = 'start_tag';
    case EndTag = 'end_tag';
    case Comment = 'comment';
    case Character = 'character';
    case Whitespace = 'whitespace';
    case Cdata = 'cdata';
    case EndOfFile = 'eof';
}
