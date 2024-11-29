<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
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
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    2.0.1
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
        $name = trim(substr($header, 0, strpos($header, ':')));

        // Handle multiple values
        if (substr_count($header, $name) > 1) {
            $values = array_map('trim', array_filter(explode($name . ':', $header)));
        // Else, handle single value
        } else {
            $values = [trim(substr($header, (strpos($header, ':') + 1)))];
        }

        $headerObject = new static($name);

        foreach ($values as $value) {
            $headerObject->addValue(Header\Value::parse($value));
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
        return (string)$this->values[$i] ?? null;
    }

    /**
     * Get index of header value
     *
     * @param  string $value
     * @return bool
     */
    public function getValueIndex(string $value): bool
    {
        $result = null;

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
        return ($this->wrap !== null);
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
        return ($this->indent !== null);
    }

    /**
     * Is the header for an attachment
     *
     * @return bool
     */
    public function isAttachment(): bool
    {
        $result = false;

        foreach ($this->values as $value) {
            if (($this->name == 'Content-Disposition') &&
                ((stripos((string)$value, 'attachment') !== false) || (stripos((string)$value, 'inline') !== false))) {
                $result = true;
                break;
            }
        }

        return $result;
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
            $header = $this->name . ': ' . $value;

            if ((int)$this->wrap != 0) {
                $header = wordwrap($header, $this->wrap, "\r\n" . $this->indent);
            }
            $headers[] = $header;
        }

        return implode("\r\n", $headers);
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
