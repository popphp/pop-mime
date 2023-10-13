<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.0.0
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
        $parameters  = [];

        if ((str_contains($value, ';')) || (str_contains($value, ','))) {
            $matches = [];
            preg_match_all('/\w+=[\\a-zA-Z0-9_\s\.\"\/]/mi', $value, $matches, PREG_OFFSET_CAPTURE);
            if (isset($matches[0]) && isset($matches[0][0]) && isset($matches[0][0][1])) {
                $val = trim(substr($value, 0, $matches[0][0][1]));
                if (str_ends_with($val, ';') || str_ends_with($val, ',')) {
                    $val = substr($val, 0, -1);
                }
                if ((stripos($val, 'Basic') !== false) || (stripos($val, 'Bearer') !== false) || (stripos($val, 'Digest') !== false)) {
                    if (str_contains($val, ' ')) {
                        $valueObject->setScheme(substr($val, 0, strpos($val, ' ')) . ' ');
                        $valueObject->setValue(substr($val, (strpos($val, ' ') + 1)));
                    } else {
                        $valueObject->setScheme($val . ' ');
                    }
                } else {
                    $valueObject->setValue($val);
                }

                $params       = trim(substr($value, $matches[0][0][1]));
                $paramValues  = [];
                $paramMatches = [];
                preg_match_all('/\w+=/mi', $params, $paramMatches, PREG_OFFSET_CAPTURE);
                foreach ($paramMatches[0] as $i => $paramMatch) {
                    if (isset($paramMatches[0][$i + 1])) {
                        $paramValues[] = trim(substr($params, $paramMatch[1], $paramMatches[0][$i + 1][1] - $paramMatch[1]));
                    } else {
                        $paramValues[] = trim(substr($params, $paramMatch[1]));
                    }
                }
                foreach ($paramValues as $pValue) {
                    [$paramName, $paramValue, $delimiter] = self::parseParameter($pValue);
                    $parameters[$paramName]  = $paramValue;
                    if ($delimiter !== null) {
                        $valueObject->setDelimiter($delimiter);
                    }
                }
            }
        } else {
            $valueObject->setValue($value);
        }

        if (!empty($parameters)) {
            $valueObject->addParameters($parameters);
        }

        return $valueObject;
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
        return ($this->delimiter !== null);
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
            if (!str_contains($value, '"') && (str_contains($value, ' ') || ($this->forceQuote))) {
                $value = '"' . $value . '"';
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
        return ($this->delimiter !== null);
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
    public function render(): string
    {
        $value = $this->scheme . $this->value;

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
     * Render the header value string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

}