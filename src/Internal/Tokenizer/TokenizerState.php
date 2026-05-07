<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Internal\Tokenizer;

/**
 * The states the tokenizer can be in. Mirrors the WHATWG HTML5 spec
 * (§13.2.5) but trimmed to the states M1.A's seed corpus exercises:
 * full coverage lands as the html5lib-tests conformance suite gets
 * wired up in M1.B+.
 *
 * @internal no BC guarantees — internals can change between minor versions
 */
enum TokenizerState
{
    case Data;
    case TagOpen;
    case EndTagOpen;
    case TagName;
    case BeforeAttributeName;
    case AttributeName;
    case AfterAttributeName;
    case BeforeAttributeValue;
    case AttributeValueDoubleQuoted;
    case AttributeValueSingleQuoted;
    case AttributeValueUnquoted;
    case AfterAttributeValueQuoted;
    case SelfClosingStartTag;
    case BogusComment;
    case MarkupDeclarationOpen;
    case CommentStart;
    case CommentStartDash;
    case Comment;
    case CommentEndDash;
    case CommentEnd;
    case Doctype;
    case BeforeDoctypeName;
    case DoctypeName;
    case AfterDoctypeName;
    case BogusDoctype;
    case ScriptData;
    case ScriptDataLessThanSign;
    case ScriptDataEndTagOpen;
    case ScriptDataEndTagName;
    case CdataSection;
    case CdataSectionBracket;
    case CdataSectionEnd;
    case CharacterReference;
    case NamedCharacterReference;
    case AmbiguousAmpersand;
    case NumericCharacterReference;
    case HexadecimalCharacterReferenceStart;
    case DecimalCharacterReferenceStart;
    case HexadecimalCharacterReference;
    case DecimalCharacterReference;
    case NumericCharacterReferenceEnd;
}
