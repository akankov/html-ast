<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Node;

/**
 * Closed discriminator for {@see Node}. Mirrors the HTML5 spec's node kinds
 * (WHATWG DOM §4.4) plus an explicit {@see self::DocumentFragment} entry for
 * fragment-mode parsing, which {@see \Dom\HTMLDocument} also models.
 */
enum NodeKind: string
{
    case Document = 'document';
    case DocumentFragment = 'document_fragment';
    case Element = 'element';
    case Attribute = 'attribute';
    case Text = 'text';
    case Comment = 'comment';
    case Doctype = 'doctype';
    case ProcessingInstruction = 'processing_instruction';
    case CDataSection = 'cdata_section';
}
