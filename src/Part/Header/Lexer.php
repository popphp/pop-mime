<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Mime\Part\Header;

/**
 * RFC 5322 header token lexer
 *
 * Tokenizes a header name/value string into atoms, quoted-strings, structural
 * delimiters, and folding whitespace, per RFC 5322 §3.2. Comments ("(...)",
 * nesting-aware) are recognized so they don't corrupt quote/delimiter
 * tracking, but are discarded rather than emitted as tokens. Malformed input
 * (an unterminated quoted-string or unbalanced comment) degrades gracefully
 * rather than throwing - this is a best-effort scanner for real-world
 * mail/HTTP headers, some of which are slightly off-spec.
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
final class Lexer
{

    const ATOM          = 'ATOM';
    const QUOTED_STRING = 'QUOTED_STRING';
    const DELIMITER     = 'DELIMITER';
    const FWS           = 'FWS';

    protected const DELIMITERS = [';' => true, ',' => true, '=' => true, ':' => true, '<' => true, '>' => true];
    protected const WHITESPACE = [' ' => true, "\t" => true, "\r" => true, "\n" => true];

    protected string $input;
    protected int    $length;
    protected int    $pos = 0;

    public function __construct(string $input)
    {
        $this->input  = $input;
        $this->length = strlen($input);
    }

    /**
     * @return Token[]
     */
    public function tokenize(): array
    {
        $tokens = [];

        while ($this->pos < $this->length) {
            $char = $this->input[$this->pos];

            if ($char === '"') {
                $tokens[] = $this->readQuotedString();
            } else if ($char === '(') {
                $this->skipComment();
            } else if (isset(self::DELIMITERS[$char])) {
                $tokens[] = new Token(self::DELIMITER, $char, $this->pos, $this->pos + 1);
                $this->pos++;
            } else if (isset(self::WHITESPACE[$char])) {
                $tokens[] = $this->readWhitespace();
            } else {
                $tokens[] = $this->readAtom();
            }
        }

        return $tokens;
    }

    protected function readQuotedString(): Token
    {
        $start = $this->pos;
        $this->pos++;
        $value = '';

        while ($this->pos < $this->length) {
            $char = $this->input[$this->pos];
            if (($char === '\\') && (($this->pos + 1) < $this->length)) {
                $value .= $this->input[$this->pos + 1];
                $this->pos += 2;
                continue;
            }
            if ($char === '"') {
                $this->pos++;
                break;
            }
            $value .= $char;
            $this->pos++;
        }

        return new Token(self::QUOTED_STRING, $value, $start, $this->pos);
    }

    protected function skipComment(): void
    {
        $this->pos++;
        $depth = 1;

        while (($this->pos < $this->length) && ($depth > 0)) {
            $char = $this->input[$this->pos];
            if (($char === '\\') && (($this->pos + 1) < $this->length)) {
                $this->pos += 2;
                continue;
            }
            if ($char === '(') {
                $depth++;
            } else if ($char === ')') {
                $depth--;
            }
            $this->pos++;
        }
    }

    protected function readWhitespace(): Token
    {
        $start = $this->pos;
        while (($this->pos < $this->length) && isset(self::WHITESPACE[$this->input[$this->pos]])) {
            $this->pos++;
        }
        return new Token(self::FWS, ' ', $start, $this->pos);
    }

    protected function readAtom(): Token
    {
        $start = $this->pos;
        while (
            ($this->pos < $this->length) &&
            !isset(self::DELIMITERS[$this->input[$this->pos]]) &&
            !isset(self::WHITESPACE[$this->input[$this->pos]]) &&
            ($this->input[$this->pos] !== '"') &&
            ($this->input[$this->pos] !== '(')
        ) {
            $this->pos++;
        }
        return new Token(self::ATOM, substr($this->input, $start, $this->pos - $start), $start, $this->pos);
    }

    /**
     * Collapse obs-fold sequences (CRLF followed by one-or-more SP/TAB) into a
     * single space, leaving other CRLFs untouched as line separators.
     */
    public static function unfold(string $input): string
    {
        return (string)preg_replace('/\r\n[ \t]+/', ' ', $input);
    }

    /**
     * Find the first occurrence of $delimiterChar that is a top-level
     * DELIMITER token in $input (i.e. not inside a quoted-string or comment).
     */
    public static function findTopLevelDelimiter(string $input, string $delimiterChar): ?Token
    {
        $tokens = (new self($input))->tokenize();

        foreach ($tokens as $token) {
            if (($token->type === self::DELIMITER) && ($token->value === $delimiterChar)) {
                return $token;
            }
        }

        return null;
    }

}
