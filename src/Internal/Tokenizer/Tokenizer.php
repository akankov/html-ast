<?php

declare(strict_types=1);

namespace Akankov\HtmlAst\Internal\Tokenizer;

use Akankov\HtmlAst\Node\AttributeQuoteStyle;
use Akankov\HtmlAst\Parser\ParseMode;
use Akankov\HtmlAst\Position\ByteRange;
use Akankov\HtmlAst\Token\CdataToken;
use Akankov\HtmlAst\Token\CharacterToken;
use Akankov\HtmlAst\Token\CommentToken;
use Akankov\HtmlAst\Token\DoctypeToken;
use Akankov\HtmlAst\Token\EndOfFileToken;
use Akankov\HtmlAst\Token\EndTagToken;
use Akankov\HtmlAst\Token\StartTagToken;
use Akankov\HtmlAst\Token\TokenAttribute;
use Akankov\HtmlAst\Token\TokenStream;
use Akankov\HtmlAst\Token\WhitespaceToken;

/**
 * WHATWG HTML5 tokenizer (§13.2.5).
 *
 * Pure pass over the input bytes — produces a {@see TokenStream} with
 * round-trip fidelity (concat of every token's `$raw` equals the input).
 * Tree construction is M1.B+ work; this class knows nothing about
 * insertion modes, foster parenting, or the document hierarchy.
 *
 * Out of M1.A scope, deliberately:
 *
 * - Adjusted-current-node-is-foreign tracking. CDATA sections are
 *   tokenized whenever encountered; the tree builder will reject them
 *   inside HTML proper.
 * - Full WHATWG named-character-reference table (~2200 entries). M1.A
 *   ships ~30 common entities; the round-trip baseline does not depend
 *   on the table being complete (unrecognized refs round-trip as their
 *   literal source bytes).
 * - The complete tokenizer-error catalog. M1.A emits a handful of common
 *   codes; the rest get filled in as the html5lib-tests conformance
 *   suite is wired up.
 *
 * No BC guarantees — the `Internal\` namespace is excluded from semver per
 * the project convention. Code outside this package should not depend on
 * this class directly.
 */
final class Tokenizer
{
    private string $input;
    private int $length;
    private int $pos;
    private TokenizerState $state;
    private TokenizerState $returnState;

    /** @var list<\Akankov\HtmlAst\Token\Token> */
    private array $tokens;

    /** @var list<TokenizerError> */
    private array $errors;

    private ?CurrentTag $currentTag = null;

    private ?CurrentDoctype $currentDoctype = null;

    private string $currentCommentData = '';
    private int $currentCommentStart = 0;

    /** plain-text run accumulator (raw bytes) */
    private string $textRaw = '';
    /** plain-text run accumulator (decoded form) */
    private string $textData = '';
    private int $textStart = 0;
    private bool $textIsWhitespaceOnly = true;

    private int $charRefCode = 0;

    private ?string $lastStartTagName = null;

    /** for ScriptDataEndTagName state — buffer of characters consumed while attempting to match an end tag */
    private string $scriptEndTagBuffer = '';
    private int $scriptEndTagStart = 0;

    /**
     * @return TokenStream a stream ending in {@see EndOfFileToken}; concat of every token's raw equals `$html`
     */
    public function tokenize(string $html, ?ParseMode $mode = null): TokenStream
    {
        $this->input = $html;
        $this->length = \strlen($html);
        $this->pos = 0;
        $this->state = TokenizerState::Data;
        $this->returnState = TokenizerState::Data;
        $this->tokens = [];
        $this->errors = [];
        $this->currentTag = null;
        $this->currentDoctype = null;
        $this->currentCommentData = '';
        $this->currentCommentStart = 0;
        $this->resetTextRun();
        $this->charRefCode = 0;
        $this->lastStartTagName = null;
        $this->scriptEndTagBuffer = '';
        $this->scriptEndTagStart = 0;

        // step() returns false at EOF, terminating the loop.
        for (;;) {
            if (! $this->step()) {
                break;
            }
        }

        $this->flushTextRun();
        $this->tokens[] = new EndOfFileToken(new ByteRange($this->length, $this->length));

        return new TokenStream($this->tokens);
    }

    /**
     * @return list<TokenizerError>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    private function step(): bool
    {
        return match ($this->state) {
            TokenizerState::Data => $this->stateData(),
            TokenizerState::TagOpen => $this->stateTagOpen(),
            TokenizerState::EndTagOpen => $this->stateEndTagOpen(),
            TokenizerState::TagName => $this->stateTagName(),
            TokenizerState::BeforeAttributeName => $this->stateBeforeAttributeName(),
            TokenizerState::AttributeName => $this->stateAttributeName(),
            TokenizerState::AfterAttributeName => $this->stateAfterAttributeName(),
            TokenizerState::BeforeAttributeValue => $this->stateBeforeAttributeValue(),
            TokenizerState::AttributeValueDoubleQuoted => $this->stateAttributeValueDoubleQuoted(),
            TokenizerState::AttributeValueSingleQuoted => $this->stateAttributeValueSingleQuoted(),
            TokenizerState::AttributeValueUnquoted => $this->stateAttributeValueUnquoted(),
            TokenizerState::AfterAttributeValueQuoted => $this->stateAfterAttributeValueQuoted(),
            TokenizerState::SelfClosingStartTag => $this->stateSelfClosingStartTag(),
            TokenizerState::BogusComment => $this->stateBogusComment(),
            TokenizerState::MarkupDeclarationOpen => $this->stateMarkupDeclarationOpen(),
            TokenizerState::CommentStart => $this->stateCommentStart(),
            TokenizerState::CommentStartDash => $this->stateCommentStartDash(),
            TokenizerState::Comment => $this->stateComment(),
            TokenizerState::CommentEndDash => $this->stateCommentEndDash(),
            TokenizerState::CommentEnd => $this->stateCommentEnd(),
            TokenizerState::Doctype => $this->stateDoctype(),
            TokenizerState::BeforeDoctypeName => $this->stateBeforeDoctypeName(),
            TokenizerState::DoctypeName => $this->stateDoctypeName(),
            TokenizerState::AfterDoctypeName => $this->stateAfterDoctypeName(),
            TokenizerState::BogusDoctype => $this->stateBogusDoctype(),
            TokenizerState::ScriptData => $this->stateScriptData(),
            TokenizerState::ScriptDataLessThanSign => $this->stateScriptDataLessThanSign(),
            TokenizerState::ScriptDataEndTagOpen => $this->stateScriptDataEndTagOpen(),
            TokenizerState::ScriptDataEndTagName => $this->stateScriptDataEndTagName(),
            TokenizerState::CdataSection => $this->stateCdataSection(),
            TokenizerState::CdataSectionBracket => $this->stateCdataSectionBracket(),
            TokenizerState::CdataSectionEnd => $this->stateCdataSectionEnd(),
            TokenizerState::CharacterReference => $this->stateCharacterReference(),
            TokenizerState::NamedCharacterReference => $this->stateNamedCharacterReference(),
            TokenizerState::AmbiguousAmpersand => $this->stateAmbiguousAmpersand(),
            TokenizerState::NumericCharacterReference => $this->stateNumericCharacterReference(),
            TokenizerState::HexadecimalCharacterReferenceStart => $this->stateHexadecimalCharacterReferenceStart(),
            TokenizerState::DecimalCharacterReferenceStart => $this->stateDecimalCharacterReferenceStart(),
            TokenizerState::HexadecimalCharacterReference => $this->stateHexadecimalCharacterReference(),
            TokenizerState::DecimalCharacterReference => $this->stateDecimalCharacterReference(),
            TokenizerState::NumericCharacterReferenceEnd => $this->stateNumericCharacterReferenceEnd(),
        };
    }

    // ---------------------------------------------------------------------
    // I/O primitives
    // ---------------------------------------------------------------------

    private function consume(): ?string
    {
        if ($this->pos >= $this->length) {
            return null;
        }

        return $this->input[$this->pos++];
    }

    private function reconsume(): void
    {
        --$this->pos;
    }

    private function startsWithCaseInsensitive(string $needle): bool
    {
        return strcasecmp(substr($this->input, $this->pos, \strlen($needle)), $needle) === 0;
    }

    // ---------------------------------------------------------------------
    // Text-run accumulator (Data + ScriptData + CdataSection)
    // ---------------------------------------------------------------------

    private function resetTextRun(): void
    {
        $this->textRaw = '';
        $this->textData = '';
        $this->textStart = $this->pos;
        $this->textIsWhitespaceOnly = true;
    }

    private function appendToTextRun(string $rawSlice, string $decoded): void
    {
        if ($this->textRaw === '') {
            $this->textStart = $this->pos - \strlen($rawSlice);
        }
        $this->textRaw .= $rawSlice;
        $this->textData .= $decoded;

        if ($this->textIsWhitespaceOnly) {
            for ($i = 0, $n = \strlen($decoded); $i < $n; ++$i) {
                if (! AsciiPredicates::isWhitespace($decoded[$i])) {
                    $this->textIsWhitespaceOnly = false;
                    break;
                }
            }
        }
    }

    private function flushTextRun(): void
    {
        if ($this->textRaw === '') {
            return;
        }

        $range = new ByteRange($this->textStart, $this->textStart + \strlen($this->textRaw));

        if ($this->textIsWhitespaceOnly) {
            $this->tokens[] = new WhitespaceToken($range, $this->textRaw);
        } else {
            $this->tokens[] = new CharacterToken($range, $this->textRaw, $this->textData);
        }

        $this->resetTextRun();
    }

    // ---------------------------------------------------------------------
    // Error reporting
    // ---------------------------------------------------------------------

    private function emitError(string $code, string $message, ?int $start = null, ?int $end = null): void
    {
        $start ??= $this->pos;
        $end ??= $this->pos;
        $this->errors[] = new TokenizerError($code, new ByteRange($start, $end), $message);
    }

    // ---------------------------------------------------------------------
    // State methods
    // ---------------------------------------------------------------------

    private function stateData(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            return false;
        }

        if ($c === '&') {
            $this->returnState = TokenizerState::Data;
            $this->state = TokenizerState::CharacterReference;

            return true;
        }

        if ($c === '<') {
            $this->flushTextRun();
            $this->state = TokenizerState::TagOpen;

            return true;
        }

        $this->appendToTextRun($c, $c);

        return true;
    }

    private function stateTagOpen(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            // EOF after `<`
            $this->emitError('eof-before-tag-name', 'eof-before-tag-name');
            $this->appendToTextRun('<', '<');
            $this->flushTextRun();

            return false;
        }

        if ($c === '!') {
            $this->state = TokenizerState::MarkupDeclarationOpen;

            return true;
        }

        if ($c === '/') {
            $this->state = TokenizerState::EndTagOpen;

            return true;
        }

        if (AsciiPredicates::isAsciiAlpha($c)) {
            $tag = new CurrentTag();
            $tag->start = $this->pos - 2; // include the '<'
            $this->currentTag = $tag;
            $this->reconsume();
            $this->state = TokenizerState::TagName;

            return true;
        }

        if ($c === '?') {
            $this->emitError('unexpected-question-mark-instead-of-tag-name', 'unexpected-question-mark-instead-of-tag-name');
            $this->currentCommentStart = $this->pos - 2;
            $this->currentCommentData = '';
            $this->reconsume();
            $this->state = TokenizerState::BogusComment;

            return true;
        }

        $this->emitError('invalid-first-character-of-tag-name', 'invalid-first-character-of-tag-name');
        $this->appendToTextRun('<', '<');
        $this->reconsume();
        $this->state = TokenizerState::Data;

        return true;
    }

    private function stateEndTagOpen(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-before-tag-name', 'eof-before-tag-name');
            $this->appendToTextRun('</', '</');
            $this->flushTextRun();

            return false;
        }

        if (AsciiPredicates::isAsciiAlpha($c)) {
            $tag = new CurrentTag();
            $tag->isEndTag = true;
            $tag->start = $this->pos - 3; // include the '</'
            $this->currentTag = $tag;
            $this->reconsume();
            $this->state = TokenizerState::TagName;

            return true;
        }

        if ($c === '>') {
            $this->emitError('missing-end-tag-name', 'missing-end-tag-name');
            $this->state = TokenizerState::Data;

            return true;
        }

        $this->emitError('invalid-first-character-of-tag-name', 'invalid-first-character-of-tag-name');
        $this->currentCommentStart = $this->pos - 3;
        $this->currentCommentData = '';
        $this->reconsume();
        $this->state = TokenizerState::BogusComment;

        return true;
    }

    private function stateTagName(): bool
    {
        \assert($this->currentTag !== null);
        $tag = $this->currentTag;
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-tag', 'eof-in-tag');

            return false;
        }

        if (AsciiPredicates::isWhitespace($c)) {
            $this->state = TokenizerState::BeforeAttributeName;

            return true;
        }

        if ($c === '/') {
            $this->state = TokenizerState::SelfClosingStartTag;

            return true;
        }

        if ($c === '>') {
            $this->emitTag();

            return true;
        }

        if (AsciiPredicates::isAsciiUpperAlpha($c)) {
            $tag->tagName .= strtolower($c);

            return true;
        }

        $tag->tagName .= $c;

        return true;
    }

    private function stateBeforeAttributeName(): bool
    {
        \assert($this->currentTag !== null);
        $tag = $this->currentTag;
        $c = $this->consume();
        if ($c === null) {
            $this->reconsume();
            $this->state = TokenizerState::AfterAttributeName;

            return true;
        }

        if (AsciiPredicates::isWhitespace($c)) {
            return true;
        }

        if ($c === '/' || $c === '>') {
            $this->reconsume();
            $this->state = TokenizerState::AfterAttributeName;

            return true;
        }

        if ($c === '=') {
            $this->emitError('unexpected-equals-sign-before-attribute-name', 'unexpected-equals-sign-before-attribute-name');
            $this->beginAttribute($tag);
            $tag->currentAttrName = '=';
            $this->state = TokenizerState::AttributeName;

            return true;
        }

        $this->beginAttribute($tag);
        $this->reconsume();
        $this->state = TokenizerState::AttributeName;

        return true;
    }

    private function beginAttribute(CurrentTag $tag): void
    {
        $tag->currentAttrName = '';
        $tag->currentAttrValue = '';
        $tag->currentAttrQuote = AttributeQuoteStyle::Empty;
        $tag->currentAttrStart = $this->pos;
    }

    private function stateAttributeName(): bool
    {
        \assert($this->currentTag !== null);
        $tag = $this->currentTag;
        $c = $this->consume();

        if (
            $c === null
            || AsciiPredicates::isWhitespace($c)
            || $c === '/'
            || $c === '>'
        ) {
            if ($c !== null) {
                $this->reconsume();
            }
            $this->state = TokenizerState::AfterAttributeName;

            return true;
        }

        if ($c === '=') {
            $this->state = TokenizerState::BeforeAttributeValue;

            return true;
        }

        if (AsciiPredicates::isAsciiUpperAlpha($c)) {
            $tag->currentAttrName .= strtolower($c);

            return true;
        }

        $tag->currentAttrName .= $c;

        return true;
    }

    private function stateAfterAttributeName(): bool
    {
        \assert($this->currentTag !== null);
        $tag = $this->currentTag;
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-tag', 'eof-in-tag');
            $this->finalizeAttribute($tag);

            return false;
        }

        if (AsciiPredicates::isWhitespace($c)) {
            return true;
        }

        if ($c === '/') {
            $this->finalizeAttribute($tag);
            $this->state = TokenizerState::SelfClosingStartTag;

            return true;
        }

        if ($c === '=') {
            $this->state = TokenizerState::BeforeAttributeValue;

            return true;
        }

        if ($c === '>') {
            $this->finalizeAttribute($tag);
            $this->emitTag();

            return true;
        }

        $this->finalizeAttribute($tag);
        $this->beginAttribute($tag);
        $this->reconsume();
        $this->state = TokenizerState::AttributeName;

        return true;
    }

    private function stateBeforeAttributeValue(): bool
    {
        \assert($this->currentTag !== null);
        $tag = $this->currentTag;
        $c = $this->consume();
        if ($c === null) {
            $this->reconsume();
            $this->state = TokenizerState::AttributeValueUnquoted;

            return true;
        }

        if (AsciiPredicates::isWhitespace($c)) {
            return true;
        }

        if ($c === '"') {
            $tag->currentAttrQuote = AttributeQuoteStyle::Double;
            $this->state = TokenizerState::AttributeValueDoubleQuoted;

            return true;
        }

        if ($c === "'") {
            $tag->currentAttrQuote = AttributeQuoteStyle::Single;
            $this->state = TokenizerState::AttributeValueSingleQuoted;

            return true;
        }

        if ($c === '>') {
            $this->emitError('missing-attribute-value', 'missing-attribute-value');
            $this->finalizeAttribute($tag);
            $this->emitTag();

            return true;
        }

        $tag->currentAttrQuote = AttributeQuoteStyle::Unquoted;
        $this->reconsume();
        $this->state = TokenizerState::AttributeValueUnquoted;

        return true;
    }

    private function stateAttributeValueDoubleQuoted(): bool
    {
        \assert($this->currentTag !== null);
        $tag = $this->currentTag;
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-tag', 'eof-in-tag');
            $this->finalizeAttribute($tag);

            return false;
        }

        if ($c === '"') {
            $this->state = TokenizerState::AfterAttributeValueQuoted;

            return true;
        }

        if ($c === '&') {
            $this->returnState = TokenizerState::AttributeValueDoubleQuoted;
            $this->state = TokenizerState::CharacterReference;

            return true;
        }

        $tag->currentAttrValue .= $c;

        return true;
    }

    private function stateAttributeValueSingleQuoted(): bool
    {
        \assert($this->currentTag !== null);
        $tag = $this->currentTag;
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-tag', 'eof-in-tag');
            $this->finalizeAttribute($tag);

            return false;
        }

        if ($c === "'") {
            $this->state = TokenizerState::AfterAttributeValueQuoted;

            return true;
        }

        if ($c === '&') {
            $this->returnState = TokenizerState::AttributeValueSingleQuoted;
            $this->state = TokenizerState::CharacterReference;

            return true;
        }

        $tag->currentAttrValue .= $c;

        return true;
    }

    private function stateAttributeValueUnquoted(): bool
    {
        \assert($this->currentTag !== null);
        $tag = $this->currentTag;
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-tag', 'eof-in-tag');
            $this->finalizeAttribute($tag);

            return false;
        }

        if (AsciiPredicates::isWhitespace($c)) {
            $this->finalizeAttribute($tag);
            $this->state = TokenizerState::BeforeAttributeName;

            return true;
        }

        if ($c === '&') {
            $this->returnState = TokenizerState::AttributeValueUnquoted;
            $this->state = TokenizerState::CharacterReference;

            return true;
        }

        if ($c === '>') {
            $this->finalizeAttribute($tag);
            $this->emitTag();

            return true;
        }

        $tag->currentAttrValue .= $c;

        return true;
    }

    private function stateAfterAttributeValueQuoted(): bool
    {
        \assert($this->currentTag !== null);
        $tag = $this->currentTag;
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-tag', 'eof-in-tag');
            $this->finalizeAttribute($tag);

            return false;
        }

        if (AsciiPredicates::isWhitespace($c)) {
            $this->finalizeAttribute($tag);
            $this->state = TokenizerState::BeforeAttributeName;

            return true;
        }

        if ($c === '/') {
            $this->finalizeAttribute($tag);
            $this->state = TokenizerState::SelfClosingStartTag;

            return true;
        }

        if ($c === '>') {
            $this->finalizeAttribute($tag);
            $this->emitTag();

            return true;
        }

        $this->emitError('missing-whitespace-between-attributes', 'missing-whitespace-between-attributes');
        $this->finalizeAttribute($tag);
        $this->reconsume();
        $this->state = TokenizerState::BeforeAttributeName;

        return true;
    }

    private function stateSelfClosingStartTag(): bool
    {
        \assert($this->currentTag !== null);
        $tag = $this->currentTag;
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-tag', 'eof-in-tag');

            return false;
        }

        if ($c === '>') {
            $tag->selfClosing = true;
            $this->emitTag();

            return true;
        }

        $this->emitError('unexpected-solidus-in-tag', 'unexpected-solidus-in-tag');
        $this->reconsume();
        $this->state = TokenizerState::BeforeAttributeName;

        return true;
    }

    private function stateBogusComment(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->emitCommentToken();

            return false;
        }

        if ($c === '>') {
            $this->emitCommentToken();
            $this->state = TokenizerState::Data;

            return true;
        }

        $this->currentCommentData .= $c;

        return true;
    }

    private function stateMarkupDeclarationOpen(): bool
    {
        // Sub-routine: peeks ahead. The two byte already consumed: '<' and '!' (3 bytes including the '!').
        $rest = substr($this->input, $this->pos);

        if (str_starts_with($rest, '--')) {
            $this->pos += 2;
            $this->currentCommentStart = $this->pos - 4; // <!--
            $this->currentCommentData = '';
            $this->state = TokenizerState::CommentStart;

            return true;
        }

        if ($this->startsWithCaseInsensitive('DOCTYPE')) {
            $this->pos += 7;
            $doctype = new CurrentDoctype();
            $doctype->start = $this->pos - 9; // <!DOCTYPE
            $this->currentDoctype = $doctype;
            $this->state = TokenizerState::Doctype;

            return true;
        }

        if (str_starts_with($rest, '[CDATA[')) {
            // Per WHATWG this only enters CDATA when adjusted-current-node-is-foreign;
            // M1.A always enters here. Tree builder rejects misuse.
            $this->pos += 7;
            $this->resetTextRun();
            $this->textStart = $this->pos - 9; // <![CDATA[
            $this->state = TokenizerState::CdataSection;

            return true;
        }

        $this->emitError('incorrectly-opened-comment', 'incorrectly-opened-comment');
        $this->currentCommentStart = $this->pos - 2; // <!
        $this->currentCommentData = '';
        $this->state = TokenizerState::BogusComment;

        return true;
    }

    private function stateCommentStart(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->reconsume();
            $this->state = TokenizerState::Comment;

            return true;
        }

        if ($c === '-') {
            $this->state = TokenizerState::CommentStartDash;

            return true;
        }

        if ($c === '>') {
            $this->emitError('abrupt-closing-of-empty-comment', 'abrupt-closing-of-empty-comment');
            $this->emitCommentToken();
            $this->state = TokenizerState::Data;

            return true;
        }

        $this->reconsume();
        $this->state = TokenizerState::Comment;

        return true;
    }

    private function stateCommentStartDash(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-comment', 'eof-in-comment');
            $this->emitCommentToken();

            return false;
        }

        if ($c === '-') {
            $this->state = TokenizerState::CommentEnd;

            return true;
        }

        if ($c === '>') {
            $this->emitError('abrupt-closing-of-empty-comment', 'abrupt-closing-of-empty-comment');
            $this->emitCommentToken();
            $this->state = TokenizerState::Data;

            return true;
        }

        $this->currentCommentData .= '-';
        $this->reconsume();
        $this->state = TokenizerState::Comment;

        return true;
    }

    private function stateComment(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-comment', 'eof-in-comment');
            $this->emitCommentToken();

            return false;
        }

        if ($c === '-') {
            $this->state = TokenizerState::CommentEndDash;

            return true;
        }

        $this->currentCommentData .= $c;

        return true;
    }

    private function stateCommentEndDash(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-comment', 'eof-in-comment');
            $this->emitCommentToken();

            return false;
        }

        if ($c === '-') {
            $this->state = TokenizerState::CommentEnd;

            return true;
        }

        $this->currentCommentData .= '-';
        $this->reconsume();
        $this->state = TokenizerState::Comment;

        return true;
    }

    private function stateCommentEnd(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-comment', 'eof-in-comment');
            $this->emitCommentToken();

            return false;
        }

        if ($c === '>') {
            $this->emitCommentToken();
            $this->state = TokenizerState::Data;

            return true;
        }

        if ($c === '-') {
            $this->currentCommentData .= '-';

            return true;
        }

        $this->currentCommentData .= '--';
        $this->reconsume();
        $this->state = TokenizerState::Comment;

        return true;
    }

    private function stateDoctype(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-doctype', 'eof-in-doctype');
            \assert($this->currentDoctype !== null);
            $dt = $this->currentDoctype;
            $dt->forceQuirks = true;
            $this->emitDoctypeToken();

            return false;
        }

        if (AsciiPredicates::isWhitespace($c)) {
            $this->state = TokenizerState::BeforeDoctypeName;

            return true;
        }

        $this->reconsume();
        $this->state = TokenizerState::BeforeDoctypeName;

        return true;
    }

    private function stateBeforeDoctypeName(): bool
    {
        \assert($this->currentDoctype !== null);
        $dt = $this->currentDoctype;
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-doctype', 'eof-in-doctype');
            $dt->forceQuirks = true;
            $this->emitDoctypeToken();

            return false;
        }

        if (AsciiPredicates::isWhitespace($c)) {
            return true;
        }

        if ($c === '>') {
            $this->emitError('missing-doctype-name', 'missing-doctype-name');
            $dt->forceQuirks = true;
            $this->emitDoctypeToken();
            $this->state = TokenizerState::Data;

            return true;
        }

        $dt->name = AsciiPredicates::isAsciiUpperAlpha($c) ? strtolower($c) : $c;
        $this->state = TokenizerState::DoctypeName;

        return true;
    }

    private function stateDoctypeName(): bool
    {
        \assert($this->currentDoctype !== null);
        $dt = $this->currentDoctype;
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-doctype', 'eof-in-doctype');
            $dt->forceQuirks = true;
            $this->emitDoctypeToken();

            return false;
        }

        if (AsciiPredicates::isWhitespace($c)) {
            $this->state = TokenizerState::AfterDoctypeName;

            return true;
        }

        if ($c === '>') {
            $this->emitDoctypeToken();
            $this->state = TokenizerState::Data;

            return true;
        }

        $dt->name = ($dt->name ?? '')
            . (AsciiPredicates::isAsciiUpperAlpha($c) ? strtolower($c) : $c);

        return true;
    }

    private function stateAfterDoctypeName(): bool
    {
        \assert($this->currentDoctype !== null);
        $dt = $this->currentDoctype;
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-doctype', 'eof-in-doctype');
            $dt->forceQuirks = true;
            $this->emitDoctypeToken();

            return false;
        }

        if (AsciiPredicates::isWhitespace($c)) {
            return true;
        }

        if ($c === '>') {
            $this->emitDoctypeToken();
            $this->state = TokenizerState::Data;

            return true;
        }

        // M1.A skips PUBLIC/SYSTEM identifier parsing — go to bogus doctype.
        $this->emitError('invalid-character-sequence-after-doctype-name', 'invalid-character-sequence-after-doctype-name');
        $dt->forceQuirks = true;
        $this->reconsume();
        $this->state = TokenizerState::BogusDoctype;

        return true;
    }

    private function stateBogusDoctype(): bool
    {
        \assert($this->currentDoctype !== null);
        $c = $this->consume();
        if ($c === null) {
            $this->emitDoctypeToken();

            return false;
        }

        if ($c === '>') {
            $this->emitDoctypeToken();
            $this->state = TokenizerState::Data;

            return true;
        }

        return true;
    }

    private function stateScriptData(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            return false;
        }

        if ($c === '<') {
            $this->flushTextRun();
            $this->state = TokenizerState::ScriptDataLessThanSign;

            return true;
        }

        $this->appendToTextRun($c, $c);

        return true;
    }

    private function stateScriptDataLessThanSign(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->appendToTextRun('<', '<');
            $this->reconsume();
            $this->state = TokenizerState::ScriptData;

            return true;
        }

        if ($c === '/') {
            $this->scriptEndTagBuffer = '';
            $this->scriptEndTagStart = $this->pos - 2; // </
            $this->state = TokenizerState::ScriptDataEndTagOpen;

            return true;
        }

        $this->appendToTextRun('<', '<');
        $this->reconsume();
        $this->state = TokenizerState::ScriptData;

        return true;
    }

    private function stateScriptDataEndTagOpen(): bool
    {
        $c = $this->consume();
        if ($c === null || ! AsciiPredicates::isAsciiAlpha($c)) {
            $this->appendToTextRun('</', '</');
            if ($c !== null) {
                $this->reconsume();
            }
            $this->state = TokenizerState::ScriptData;

            return true;
        }

        $this->reconsume();
        $this->state = TokenizerState::ScriptDataEndTagName;

        return true;
    }

    private function stateScriptDataEndTagName(): bool
    {
        $c = $this->consume();

        if ($c !== null && AsciiPredicates::isAsciiAlpha($c)) {
            $this->scriptEndTagBuffer .= AsciiPredicates::isAsciiUpperAlpha($c) ? strtolower($c) : $c;

            return true;
        }

        // We need to decide: was this the appropriate end tag for the open script tag?
        if (
            $this->scriptEndTagBuffer === 'script'
            && $c !== null
            && (AsciiPredicates::isWhitespace($c) || $c === '/' || $c === '>')
        ) {
            // Treat as a real end tag: emit </script> with whatever attribute parsing follows.
            $tag = new CurrentTag();
            $tag->tagName = $this->scriptEndTagBuffer;
            $tag->isEndTag = true;
            $tag->start = $this->scriptEndTagStart;
            $this->currentTag = $tag;

            if ($c === '/') {
                $this->state = TokenizerState::SelfClosingStartTag;

                return true;
            }

            if (AsciiPredicates::isWhitespace($c)) {
                $this->state = TokenizerState::BeforeAttributeName;

                return true;
            }

            // c === '>'
            $this->emitTag();

            return true;
        }

        // Not the appropriate end tag — treat as character data. Reconsume what we have.
        $this->appendToTextRun('</' . $this->scriptEndTagBuffer, '</' . $this->scriptEndTagBuffer);
        if ($c !== null) {
            $this->reconsume();
        }
        $this->state = TokenizerState::ScriptData;

        return true;
    }

    private function stateCdataSection(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('eof-in-cdata', 'eof-in-cdata');
            $this->emitCdataToken();

            return false;
        }

        if ($c === ']') {
            $this->state = TokenizerState::CdataSectionBracket;

            return true;
        }

        $this->textRaw .= $c;
        $this->textData .= $c;

        return true;
    }

    private function stateCdataSectionBracket(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->textRaw .= ']';
            $this->textData .= ']';
            $this->reconsume();
            $this->state = TokenizerState::CdataSection;

            return true;
        }

        if ($c === ']') {
            $this->state = TokenizerState::CdataSectionEnd;

            return true;
        }

        $this->textRaw .= ']';
        $this->textData .= ']';
        $this->reconsume();
        $this->state = TokenizerState::CdataSection;

        return true;
    }

    private function stateCdataSectionEnd(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->textRaw .= ']]';
            $this->textData .= ']]';
            $this->reconsume();
            $this->state = TokenizerState::CdataSection;

            return true;
        }

        if ($c === ']') {
            $this->textRaw .= ']';
            $this->textData .= ']';

            return true;
        }

        if ($c === '>') {
            $this->emitCdataToken();
            $this->state = TokenizerState::Data;

            return true;
        }

        $this->textRaw .= ']]';
        $this->textData .= ']]';
        $this->reconsume();
        $this->state = TokenizerState::CdataSection;

        return true;
    }

    private function stateCharacterReference(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->appendInReturnState('&', '&');
            $this->reconsume();
            $this->state = $this->returnState;

            return true;
        }

        if ($c === '#') {
            $this->charRefCode = 0;
            $this->state = TokenizerState::NumericCharacterReference;

            return true;
        }

        if (AsciiPredicates::isAsciiAlphanumeric($c)) {
            // Try to match a named reference starting at the '&'.
            $ampOffset = $this->pos - 2; // we consumed '&' then this letter
            $match = CharacterReference::matchNamed($this->input, $ampOffset);

            if ($match !== null) {
                $rawSlice = substr($this->input, $ampOffset, $match['consumed']);
                $this->pos = $ampOffset + $match['consumed'];
                $this->appendInReturnState($rawSlice, $match['decoded']);
                $this->state = $this->returnState;

                return true;
            }

            // No match — fall through to ambiguous-ampersand recovery.
            $this->appendInReturnState('&', '&');
            $this->reconsume();
            $this->state = TokenizerState::AmbiguousAmpersand;

            return true;
        }

        $this->appendInReturnState('&', '&');
        $this->reconsume();
        $this->state = $this->returnState;

        return true;
    }

    private function stateNamedCharacterReference(): bool
    {
        // Folded into stateCharacterReference for M1.A; this method exists so
        // the state enum case is covered. WHATWG splits these for a reason
        // (named-ref scanning is its own sub-machine), but for our seed entity
        // table the simpler approach is fine.
        $this->state = TokenizerState::Data;

        return true;
    }

    private function stateAmbiguousAmpersand(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->reconsume();
            $this->state = $this->returnState;

            return true;
        }

        if (AsciiPredicates::isAsciiAlphanumeric($c)) {
            $this->appendInReturnState($c, $c);

            return true;
        }

        if ($c === ';') {
            $this->emitError('unknown-named-character-reference', 'unknown-named-character-reference');
            $this->reconsume();
            $this->state = $this->returnState;

            return true;
        }

        $this->reconsume();
        $this->state = $this->returnState;

        return true;
    }

    private function stateNumericCharacterReference(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('absence-of-digits-in-numeric-character-reference', 'absence-of-digits-in-numeric-character-reference');
            $this->appendInReturnState('&#', '&#');
            $this->reconsume();
            $this->state = $this->returnState;

            return true;
        }

        if ($c === 'x' || $c === 'X') {
            $this->state = TokenizerState::HexadecimalCharacterReferenceStart;

            return true;
        }

        $this->reconsume();
        $this->state = TokenizerState::DecimalCharacterReferenceStart;

        return true;
    }

    private function stateHexadecimalCharacterReferenceStart(): bool
    {
        $c = $this->consume();
        if ($c === null || ! AsciiPredicates::isAsciiHexDigit($c)) {
            $this->emitError('absence-of-digits-in-numeric-character-reference', 'absence-of-digits-in-numeric-character-reference');
            $this->appendInReturnState('&#x', '&#x');
            if ($c !== null) {
                $this->reconsume();
            }
            $this->state = $this->returnState;

            return true;
        }

        $this->reconsume();
        $this->state = TokenizerState::HexadecimalCharacterReference;

        return true;
    }

    private function stateDecimalCharacterReferenceStart(): bool
    {
        $c = $this->consume();
        if ($c === null || ! AsciiPredicates::isAsciiDigit($c)) {
            $this->emitError('absence-of-digits-in-numeric-character-reference', 'absence-of-digits-in-numeric-character-reference');
            $this->appendInReturnState('&#', '&#');
            if ($c !== null) {
                $this->reconsume();
            }
            $this->state = $this->returnState;

            return true;
        }

        $this->reconsume();
        $this->state = TokenizerState::DecimalCharacterReference;

        return true;
    }

    private function stateHexadecimalCharacterReference(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('missing-semicolon-after-character-reference', 'missing-semicolon-after-character-reference');
            $this->state = TokenizerState::NumericCharacterReferenceEnd;

            return true;
        }

        if (AsciiPredicates::isAsciiDigit($c)) {
            $this->charRefCode = $this->charRefCode * 16 + (\ord($c) - 0x30);

            return true;
        }

        if ($c >= 'A' && $c <= 'F') {
            $this->charRefCode = $this->charRefCode * 16 + (\ord($c) - 0x37);

            return true;
        }

        if ($c >= 'a' && $c <= 'f') {
            $this->charRefCode = $this->charRefCode * 16 + (\ord($c) - 0x57);

            return true;
        }

        if ($c === ';') {
            $this->state = TokenizerState::NumericCharacterReferenceEnd;

            return true;
        }

        $this->emitError('missing-semicolon-after-character-reference', 'missing-semicolon-after-character-reference');
        $this->reconsume();
        $this->state = TokenizerState::NumericCharacterReferenceEnd;

        return true;
    }

    private function stateDecimalCharacterReference(): bool
    {
        $c = $this->consume();
        if ($c === null) {
            $this->emitError('missing-semicolon-after-character-reference', 'missing-semicolon-after-character-reference');
            $this->state = TokenizerState::NumericCharacterReferenceEnd;

            return true;
        }

        if (AsciiPredicates::isAsciiDigit($c)) {
            $this->charRefCode = $this->charRefCode * 10 + (\ord($c) - 0x30);

            return true;
        }

        if ($c === ';') {
            $this->state = TokenizerState::NumericCharacterReferenceEnd;

            return true;
        }

        $this->emitError('missing-semicolon-after-character-reference', 'missing-semicolon-after-character-reference');
        $this->reconsume();
        $this->state = TokenizerState::NumericCharacterReferenceEnd;

        return true;
    }

    private function stateNumericCharacterReferenceEnd(): bool
    {
        // Decode the accumulated codepoint, consuming back to the '&' for the raw bytes.
        // We need to know where the reference started. Walk back from current pos.
        $endPos = $this->pos;
        $startPos = $endPos - 1;
        // Find the '&' by scanning backwards (cheap; references are short)
        while ($startPos > 0 && $this->input[$startPos] !== '&') {
            --$startPos;
        }
        $rawSlice = substr($this->input, $startPos, $endPos - $startPos);
        $decoded = CharacterReference::decodeNumeric($this->charRefCode);

        $this->appendInReturnState($rawSlice, $decoded);
        $this->state = $this->returnState;

        return true;
    }

    /**
     * Append decoded characters in the appropriate slot for the current return
     * state (text run, attribute value, etc.). The `$rawSlice` is the source
     * bytes the entity occupies (for round-trip fidelity).
     */
    private function appendInReturnState(string $rawSlice, string $decoded): void
    {
        if (
            $this->returnState === TokenizerState::AttributeValueDoubleQuoted
            || $this->returnState === TokenizerState::AttributeValueSingleQuoted
            || $this->returnState === TokenizerState::AttributeValueUnquoted
        ) {
            \assert($this->currentTag !== null);
            $tag = $this->currentTag;
            $tag->currentAttrValue .= $decoded;
            // The raw bytes will be rolled into the tag's overall raw via emitTag().

            return;
        }

        // Default: append to the running text accumulator.
        $this->appendToTextRun($rawSlice, $decoded);
    }

    // ---------------------------------------------------------------------
    // Token emission helpers
    // ---------------------------------------------------------------------

    private function finalizeAttribute(CurrentTag $tag): void
    {
        if ($tag->currentAttrName === '') {
            return;
        }

        // Detect duplicates per WHATWG (ignore subsequent occurrences of the same name).
        foreach ($tag->attributes as $existing) {
            if ($existing->name === $tag->currentAttrName) {
                $this->emitError('duplicate-attribute', 'duplicate-attribute');
                $tag->currentAttrName = '';
                $tag->currentAttrValue = '';

                return;
            }
        }

        $attrEnd = $this->pos;
        $tag->attributes[] = new TokenAttribute(
            new ByteRange($tag->currentAttrStart, $attrEnd),
            $tag->currentAttrName,
            $tag->currentAttrValue,
            $tag->currentAttrQuote,
        );
        $tag->currentAttrName = '';
        $tag->currentAttrValue = '';
    }

    private function emitTag(): void
    {
        \assert($this->currentTag !== null);
        $tag = $this->currentTag;
        $this->finalizeAttribute($tag);

        $start = $tag->start;
        $end = $this->pos;
        $raw = substr($this->input, $start, $end - $start);
        $range = new ByteRange($start, $end);

        if ($tag->isEndTag) {
            $token = new EndTagToken(
                $range,
                $raw,
                $tag->tagName,
                $tag->attributes,
                $tag->selfClosing,
            );
        } else {
            $token = new StartTagToken(
                $range,
                $raw,
                $tag->tagName,
                $tag->attributes,
                $tag->selfClosing,
            );
            $this->lastStartTagName = $tag->tagName;
        }

        $this->tokens[] = $token;
        $this->currentTag = null;
        $this->state = ($token instanceof StartTagToken && $this->lastStartTagName === 'script')
            ? TokenizerState::ScriptData
            : TokenizerState::Data;
    }

    private function emitCommentToken(): void
    {
        $end = $this->pos;
        $raw = substr($this->input, $this->currentCommentStart, $end - $this->currentCommentStart);
        $this->tokens[] = new CommentToken(
            new ByteRange($this->currentCommentStart, $end),
            $raw,
            $this->currentCommentData,
        );
        $this->currentCommentData = '';
    }

    private function emitDoctypeToken(): void
    {
        \assert($this->currentDoctype !== null);
        $dt = $this->currentDoctype;
        $end = $this->pos;
        $raw = substr($this->input, $dt->start, $end - $dt->start);
        $this->tokens[] = new DoctypeToken(
            new ByteRange($dt->start, $end),
            $raw,
            $dt->name,
            $dt->publicId,
            $dt->systemId,
            $dt->forceQuirks,
        );
        $this->currentDoctype = null;
    }

    private function emitCdataToken(): void
    {
        $end = $this->pos;
        $start = $this->textStart;
        $raw = substr($this->input, $start, $end - $start);
        $this->tokens[] = new CdataToken(
            new ByteRange($start, $end),
            $raw,
            $this->textData,
        );
        $this->resetTextRun();
    }
}
