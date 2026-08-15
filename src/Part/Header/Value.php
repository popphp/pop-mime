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
 * MIME part header value class
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Value
{

    /**
     * Header value scheme
     * @var ?string
     */
    protected ?string $scheme = null;

    /**
     * Header value
     * @var ?string
     */
    protected ?string $value = null;

    /**
     * Header value parameters
     * @var array
     */
    protected array $parameters = [];

    /**
     * Header value delimiter
     * @var string
     */
    protected string $delimiter = ';';

    /**
     * Force quotes for parameter values
     * @var bool
     */
    protected bool $forceQuote = false;

    /**
     * Constructor
     *
     * Instantiate the header value object
     *
     * @param ?string $value
     * @param ?string $scheme
     * @param array   $parameters
     * @param bool    $forceQuote
     */
    public function __construct(?string $value = null, ?string $scheme = null, array $parameters = [], bool $forceQuote = false)
    {
        if ($value !== null) {
            $this->setValue($value);
        }
        if ($scheme !== null) {
            $this->setScheme($scheme);
        }
        if (!empty($parameters)) {
            $this->addParameters($parameters);
        }
        if ($forceQuote) {
            $this->setForceQuote($forceQuote);
        }
    }

    /**
     * Parse header value
     *
     * @param  string $value
     * @return Value
     */
    public static function parse(string $value): Value
    {
        $valueObject = new Value();
        $trimmed     = trim($value);

        foreach (['Basic', 'Bearer', 'Digest'] as $schemeName) {
            $schemeLength = strlen($schemeName);
            if ((strncasecmp($trimmed, $schemeName, $schemeLength) === 0) &&
                ((strlen($trimmed) === $schemeLength) || ($trimmed[$schemeLength] === ' '))) {
                $valueObject->setScheme($schemeName . ' ');
                $trimmed = ltrim(substr($trimmed, $schemeLength));
                break;
            }
        }

        $source             = $trimmed;
        $tokens             = (new Lexer($source))->tokenize();
        $encodedWordRanges  = EncodedWord::findRanges($source);

        $segments   = [[]];
        $delimiters = [];

        foreach ($tokens as $token) {
            if (($token->type === Lexer::DELIMITER) && (($token->value === ';') || ($token->value === ','))) {
                $delimiters[] = $token->value;
                $segments[]   = [];
            } else {
                $segments[count($segments) - 1][] = $token;
            }
        }

        $firstSegment    = self::trimFws($segments[0]);
        $hasLeadingValue = true;
        if (count($segments) > 1) {
            foreach ($firstSegment as $token) {
                if (($token->type === Lexer::DELIMITER) && ($token->value === '=') &&
                    !self::isInsideEncodedWord($token, $encodedWordRanges)) {
                    $hasLeadingValue = false;
                    break;
                }
            }
        }

        $valueParts      = [];
        $valuePartsDelim = [];
        $parameters      = [];

        foreach ($segments as $i => $segmentTokens) {
            $segmentTokens = self::trimFws($segmentTokens);

            if (($i === 0) && $hasLeadingValue) {
                $spliced = self::spliceSegment($source, $segmentTokens);
                if ($spliced !== '') {
                    $valueParts[] = $spliced;
                }
                continue;
            }

            $eqIndex = null;
            foreach ($segmentTokens as $j => $token) {
                if (($token->type === Lexer::DELIMITER) && ($token->value === '=') &&
                    !self::isInsideEncodedWord($token, $encodedWordRanges)) {
                    $eqIndex = $j;
                    break;
                }
            }

            if ($eqIndex === null) {
                $spliced = self::spliceSegment($source, $segmentTokens);
                if ($spliced !== '') {
                    if (!empty($valueParts)) {
                        $valuePartsDelim[] = $delimiters[$i - 1];
                    }
                    $valueParts[] = $spliced;
                }
                continue;
            }

            $paramName  = self::renderSegment(array_slice($segmentTokens, 0, $eqIndex));
            $paramValue = self::renderSegment(array_slice($segmentTokens, $eqIndex + 1));
            if ($paramName !== '') {
                $parameters[$paramName] = $paramValue;
            }
        }

        if (!empty($valueParts)) {
            $mainValue = array_shift($valueParts);
            foreach ($valueParts as $k => $part) {
                $mainValue .= $valuePartsDelim[$k] . ' ' . $part;
            }
            $valueObject->setValue($mainValue);
        }

        if (!empty($parameters)) {
            $valueObject->addParameters($parameters);
        }
        if (!empty($delimiters)) {
            $valueObject->setDelimiter($delimiters[count($delimiters) - 1]);
        }

        return $valueObject;
    }

    /**
     * Trim leading/trailing FWS tokens from a token list
     *
     * @param  Token[] $tokens
     * @return Token[]
     */
    protected static function trimFws(array $tokens): array
    {
        while (!empty($tokens) && ($tokens[array_key_first($tokens)]->type === Lexer::FWS)) {
            array_shift($tokens);
        }
        while (!empty($tokens) && ($tokens[array_key_last($tokens)]->type === Lexer::FWS)) {
            array_pop($tokens);
        }
        return array_values($tokens);
    }

    /**
     * Concatenate a token list's values back into a string
     *
     * @param  Token[] $tokens
     * @return string
     */
    protected static function renderSegment(array $tokens): string
    {
        $value = '';
        foreach (self::trimFws($tokens) as $token) {
            $value .= $token->value;
        }
        return $value;
    }

    /**
     * Splice a token span's exact original text out of the source string,
     * by first/last token offset - preserves comments, escapes, and
     * whitespace exactly as written, unlike reconstructing from token values.
     *
     * @param  string  $source
     * @param  Token[] $tokens
     * @return string
     */
    protected static function spliceSegment(string $source, array $tokens): string
    {
        $tokens = self::trimFws($tokens);
        if (empty($tokens)) {
            return '';
        }
        $first = $tokens[array_key_first($tokens)];
        $last  = $tokens[array_key_last($tokens)];
        return substr($source, $first->start, $last->end - $first->start);
    }

    /**
     * @param  Token $token
     * @param  array $ranges
     * @return bool
     */
    protected static function isInsideEncodedWord(Token $token, array $ranges): bool
    {
        foreach ($ranges as $range) {
            if (($token->start >= $range['start']) && ($token->start < $range['end'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Parse a parameter value
     *
     * @param  string $parameter
     * @return array
     */
    public static function parseParameter(string $parameter): array
    {
        $paramName  = substr($parameter, 0, strpos($parameter, '='));
        $paramValue = substr($parameter, (strpos($parameter, '=') + 1));
        $delimiter  = null;
        if (str_ends_with($paramValue, ';') || str_ends_with($paramValue, ',')) {
            $delimiter = (str_ends_with($paramValue, ';')) ? ';' : ',';
            $paramValue = substr($paramValue, 0, -1);
        }
        if ((str_starts_with($paramValue, '"')) && (str_ends_with($paramValue, '"'))) {
            $paramValue = substr($paramValue, 1);
            $paramValue = substr($paramValue, 0, -1);
        }
        return [$paramName, $paramValue, $delimiter];
    }

    /**
     * Set the header value scheme
     *
     * @param  string $scheme
     * @return Value
     */
    public function setScheme(string $scheme): Value
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * Get the header value scheme
     *
     * @return string|null
     */
    public function getScheme(): string|null
    {
        return $this->scheme;
    }

    /**
     * Has a header value scheme
     *
     * @return bool
     */
    public function hasScheme(): bool
    {
        return ($this->scheme !== null);
    }

    /**
     * Set the header value
     *
     * @param  string $value
     * @return Value
     */
    public function setValue(string $value): Value
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get the header value
     *
     * @return string|null
     */
    public function getValue(): string|null
    {
        return $this->value;
    }

    /**
     * Get the header value, decoded of any RFC 2047 encoded-words
     *
     * @return string|null
     */
    public function getDecodedValue(): string|null
    {
        return ($this->value !== null) ? EncodedWord::decode($this->value) : null;
    }

    /**
     * Add the header value parameters
     *
     * @param  array $parameters
     * @return Value
     */
    public function addParameters(array $parameters): Value
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Set a header value parameter
     *
     * @param string $name
     * @param string $value
     * @return Value
     */
    public function addParameter(string $name, string $value): Value
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * Get the header value parameters
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get the header value parameters as string
     *
     * @throws Exception
     * @return string
     */
    public function getParametersAsString(): string
    {
        if (!$this->hasDelimiter()) {
            throw new Exception('Error: No delimiter has been set.');
        }

        $parameters = [];

        foreach ($this->parameters as $name => $value) {
            $needsQuoting = $this->forceQuote || (bool)preg_match('/[\s;,="\\\\]/', $value);
            if ($needsQuoting) {
                $value = '"' . addcslashes($value, '"\\') . '"';
            }
            $parameters[] = $name . '=' . $value;
        }

        return implode($this->delimiter . ' ', $parameters);
    }

    /**
     * Get a header value parameter
     *
     * @param  string $name
     * @return string|null
     */
    public function getParameter(string $name): string|null
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * Has header value parameters
     *
     * @return bool
     */
    public function hasParameters(): bool
    {
        return (count($this->parameters) > 0);
    }

    /**
     * Has a header value parameter
     *
     * @param  string $name
     * @return bool
     */
    public function hasParameter(string $name): bool
    {
        return (isset($this->parameters[$name]));
    }

    /**
     * Set the header value delimiter
     *
     * @param  string $delimiter
     * @return Value
     */
    public function setDelimiter(string $delimiter): Value
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * Get the header value delimiter
     *
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * Has a header value delimiter
     *
     * @return bool
     */
    public function hasDelimiter(): bool
    {
        return ($this->delimiter !== '');
    }

    /**
     * Set the header value delimiter
     *
     * @param  bool $forceQuote
     * @return Value
     */
    public function setForceQuote(bool $forceQuote = false): Value
    {
        $this->forceQuote = $forceQuote;
        return $this;
    }

    /**
     * Is set to force quote
     *
     * @return bool
     */
    public function isForceQuote(): bool
    {
        return $this->forceQuote;
    }

    /**
     * Render the header value string
     *
     * @throws Exception
     * @return string
     */
    public function render(?string $headerName = null): string
    {
        $value = $this->scheme . self::encodeValueForHeader((string)$this->value, $headerName);

        if (count($this->parameters) > 0) {
            $parameters = $this->getParametersAsString();
            if (!str_ends_with($value, ' ')) {
                $value .= $this->delimiter . ' ';
            }
            $value .= $parameters;
        }

        return $value;
    }

    /**
     * Header names whose value is a structured address (mailbox-list or
     * address-list per RFC 5322) - for these, the value is parsed via
     * AddressList and each address's display name is RFC 2047-encoded
     * independently, never the address itself. Groups and obs-* forms
     * aren't parsed structurally - see AddressList::parse().
     *
     * @var array
     */
    protected const ADDRESS_HEADER_NAMES = [
        'to', 'from', 'cc', 'bcc', 'reply-to', 'sender',
        'resent-to', 'resent-from', 'resent-cc', 'resent-bcc', 'resent-sender',
    ];

    /**
     * @param  string  $value
     * @param  ?string $headerName
     * @return string
     */
    protected static function encodeValueForHeader(string $value, ?string $headerName): string
    {
        if (($headerName !== null) && in_array(strtolower($headerName), self::ADDRESS_HEADER_NAMES, true)) {
            return AddressList::parse($value)->render();
        }
        return EncodedWord::encode($value);
    }

    /**
     * Render the header value string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

}
