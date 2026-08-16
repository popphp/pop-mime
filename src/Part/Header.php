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
namespace Pop\Mime\Part;

/**
 * MIME part header class
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Header
{

    /**
     * Header name
     * @var ?string
     */
    protected ?string$name = null;

    /**
     * Header values
     * @var array
     */
    protected array $values = [];

    /**
     * Header wrap
     * @var int
     */
    protected int $wrap = 0;

    /**
     * Header wrap indent
     * @var string
     */
    protected string $indent = "\t";

    /**
     * Constructor
     *
     * Instantiate the header object
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __construct(string $name, mixed $value = null)
    {
        $this->setName($name);

        if ($value !== null) {
            if (is_array($value)) {
                $this->addValues($value);
            } else {
                $this->addValue($value);
            }
        }
    }

    /**
     * Parse header
     *
     * @param  string $header
     * @return Header
     */
    public static function parse(string $header): Header
    {
        $unfolded = Header\Lexer::unfold(trim($header, "\r\n"));
        $lines    = explode("\r\n", $unfolded);

        $firstLine  = $lines[0];
        $colonToken = Header\Lexer::findTopLevelDelimiter($firstLine, ':');

        if ($colonToken === null) {
            return new static(trim($firstLine));
        }

        $name         = trim(substr($firstLine, 0, $colonToken->start));
        $headerObject = new static($name);
        $headerObject->addValue(Header\Value::parse(substr($firstLine, $colonToken->end)));

        for ($i = 1, $count = count($lines); $i < $count; $i++) {
            $line = $lines[$i];
            if ($line === '') {
                continue;
            }

            $lineColonToken = Header\Lexer::findTopLevelDelimiter($line, ':');
            if ($lineColonToken === null) {
                continue;
            }

            $lineName = trim(substr($line, 0, $lineColonToken->start));
            if ($lineName === $name) {
                $headerObject->addValue(Header\Value::parse(substr($line, $lineColonToken->end)));
            }
        }

        return $headerObject;
    }

    /**
     * Set the header name
     *
     * @param  string $name
     * @return Header
     */
    public function setName(string $name): Header
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the header name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Add header values
     *
     * @param  array $values
     * @return Header
     */
    public function addValues(array $values): Header
    {
        foreach ($values as $value) {
            $this->addValue($value);
        }

        return $this;
    }

    /**
     * Add a header value
     *
     * @param  Header\Value|string $value
     * @param  ?string             $scheme
     * @param  array               $parameters
     * @return Header
     */
    public function addValue(Header\Value|string $value, ?string $scheme = null, array $parameters = []): Header
    {
        if (is_string($value)) {
            $value = new Header\Value($value, $scheme, $parameters);
        }
        $this->values[] = $value;
        return $this;
    }

    /**
     * Get the header values
     *
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Get a header value object
     *
     * @param  int $i
     * @return Header\Value|null
     */
    public function getValue(int $i = 0): Header\Value|null
    {
        return $this->values[$i] ?? null;
    }

    /**
     * Get a header value as a string
     *
     * @param  int $i
     * @return string|null
     */
    public function getValueAsString(int $i = 0): string|null
    {
        return isset($this->values[$i]) ? (string)$this->values[$i] : null;
    }

    /**
     * Get index of header value
     *
     * @param  string $value
     * @return int|bool
     */
    public function getValueIndex(string $value): int|bool
    {
        $result = false;

        foreach ($this->values as $i => $val) {
            if ($val->getValue() == $value) {
                $result = $i;
                break;
            }
        }

        return $result;
    }

    /**
     * Determine if the header has a value at index
     *
     * @param  int $i
     * @return bool
     */
    public function hasValueAtIndex(int $i): bool
    {
        return (isset($this->values[$i]));
    }

    /**
     * Determine if the header has a value
     *
     * @param  string $value
     * @return bool
     */
    public function hasValue(string $value): bool
    {
        $result = false;

        foreach ($this->values as $val) {
            if ($val->getValue() == $value) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Get the header values as strings
     *
     * @param  ?string $delimiter
     * @return string|array
     */
    public function getValuesAsStrings(?string $delimiter = null): string|array
    {
        if (count($this->values) == 1) {
            return (string)$this->values[0];
        } else {
            $values = [];
            foreach ($this->values as $value) {
                $values[] = (string)$value;
            }

            return ($delimiter !== null) ? implode($delimiter, $values) : $values;
        }
    }

    /**
     * Set the header wrap
     *
     * @param  int $wrap
     * @return Header
     */
    public function setWrap(int $wrap): Header
    {
        $this->wrap = (int)$wrap;
        return $this;
    }

    /**
     * Get the header wrap
     *
     * @return int
     */
    public function getWrap(): int
    {
        return $this->wrap;
    }

    /**
     * Has header wrap
     *
     * @return bool
     */
    public function hasWrap(): bool
    {
        return ($this->wrap !== 0);
    }

    /**
     * Set the header wrap indent
     *
     * @param  string $indent
     * @return Header
     */
    public function setIndent(string $indent): Header
    {
        $this->indent = $indent;
        return $this;
    }

    /**
     * Get the header wrap indent
     *
     * @return string
     */
    public function getIndent(): string
    {
        return $this->indent;
    }

    /**
     * Has header wrap indent
     *
     * @return bool
     */
    public function hasIndent(): bool
    {
        return ($this->indent !== '');
    }

    /**
     * Is the header for an attachment
     *
     * @return bool
     */
    public function isAttachment(): bool
    {
        if ($this->name != 'Content-Disposition') {
            return false;
        }

        foreach ($this->values as $value) {
            if ((stripos((string)$value, 'attachment') !== false) || (stripos((string)$value, 'inline') !== false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render the header string
     *
     * @return string
     */
    public function render(): string
    {
        $headers = [];

        foreach ($this->values as $value) {
            $header = $this->name . ': ' . $value->render($this->name);
            if ((int)$this->wrap !== 0) {
                $header = $this->fold($header);
            }
            $headers[] = $header;
        }

        return implode("\r\n", $headers);
    }

    /**
     * Fold a rendered header line at RFC 5322 structural boundaries - only
     * ever after a DELIMITER token or at an FWS token, never inside an ATOM
     * or a QUOTED_STRING.
     *
     * @param  string $header
     * @return string
     */
    protected function fold(string $header): string
    {
        $tokens = (new Header\Lexer($header))->tokenize();
        $ranges = Header\EncodedWord::findRanges($header);

        foreach ($tokens as $i => $token) {
            if (($token->type === Header\Lexer::DELIMITER) && ($token->value === '<')) {
                for ($j = $i + 1, $count = count($tokens); $j < $count; $j++) {
                    if (($tokens[$j]->type === Header\Lexer::DELIMITER) && ($tokens[$j]->value === '>')) {
                        $ranges[] = ['start' => $token->start, 'end' => $tokens[$j]->end];
                        break;
                    }
                }
            }
        }

        $result    = '';
        $current   = '';
        $lastEnd   = 0;
        $skipUntil = 0;

        foreach ($tokens as $token) {
            if ($token->start < $skipUntil) {
                continue;
            }

            $range = null;
            foreach ($ranges as $candidate) {
                if ($candidate['start'] === $token->start) {
                    $range = $candidate;
                    break;
                }
            }

            if ($range !== null) {
                $piece     = substr($header, $lastEnd, $range['end'] - $lastEnd);
                $lastEnd   = $range['end'];
                $skipUntil = $range['end'];
                $isFws     = false;
            } else {
                $piece   = substr($header, $lastEnd, $token->end - $lastEnd);
                $lastEnd = $token->end;
                $isFws   = ($token->type === Header\Lexer::FWS);
            }

            if (($current !== '') && ((strlen($current) + strlen($piece)) > $this->wrap)) {
                $result  .= rtrim($current) . "\r\n" . $this->indent;
                $current  = '';
                if ($isFws) {
                    continue;
                }
            }

            $current .= $piece;
        }

        $current .= substr($header, $lastEnd);

        return $result . $current;
    }

    /**
     * Render the header string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

}
