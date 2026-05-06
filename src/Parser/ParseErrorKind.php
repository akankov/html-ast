<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Parser;

/**
 * Discriminator for {@see ParseError}. The case set is intentionally kept
 * minimal in M0 and will be expanded during M1 to cover the WHATWG HTML5
 * tokenizer / tree-construction error codes that the chosen parser backend
 * actually surfaces (the case enumeration depends on which parser
 * implementation lands first — see PLAN §4 O1).
 */
enum ParseErrorKind: string
{
    case TokenizerError = 'tokenizer_error';
    case TreeConstructionError = 'tree_construction_error';
    case UnexpectedEndOfInput = 'unexpected_end_of_input';
    case Other = 'other';
}
