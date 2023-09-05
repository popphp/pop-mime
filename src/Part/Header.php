<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.2.0
 */
class Header
{

    /**
     * Header name
     * @var string
     */
    protected $name = null;

    /**
     * Header values
     * @var array
     */
    protected $values = [];

    /**
     * Header wrap
     * @var int
     */
    protected $wrap = 0;

    /**
     * Header wrap indent
     * @var string
     */
    protected $indent = "\t";

    /**
     * Constructor
     *
     * Instantiate the header object
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __construct($name, $value = null)
    {
        $this->setName($name);

        if (null !== $value) {
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
    public static function parse($header)
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
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the header name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add header values
     *
     * @param  array $values
     * @return Header
     */
    public function addValues(array $values)
    {
        foreach ($values as $value) {
            $this->addValue($value);
        }

        return $this;
    }

    /**
     * Add a header value
     *
     * @param  string $value
     * @param  string $scheme
     * @param  array  $parameters
     * @return Header
     */
    public function addValue($value, $scheme = null, array $parameters = [])
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
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Get a header value object
     *
     * @param  int $i
     * @return Header\Value|null
     */
    public function getValue($i = 0)
    {
        return (isset($this->values[$i])) ? $this->values[$i] : null;
    }

    /**
     * Get a header value as a string
     *
     * @param  int $i
     * @return string|null
     */
    public function getValueAsString($i = 0)
    {
        return (isset($this->values[$i])) ? (string)$this->values[$i] : null;
    }

    /**
     * Get index of header value
     *
     * @param  string $value
     * @return boolean
     */
    public function getValueIndex($value)
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
     * @return boolean
     */
    public function hasValueAtIndex($i)
    {
        return (isset($this->values[$i]));
    }

    /**
     * Determine if the header has a value
     *
     * @param  string $value
     * @return boolean
     */
    public function hasValue($value)
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
     * @param  string $delimiter
     * @return string|array
     */
    public function getValuesAsStrings($delimiter = null)
    {
        if (count($this->values) == 1) {
            return (string)$this->values[0];
        } else {
            $values = [];
            foreach ($this->values as $value) {
                $values[] = (string)$value;
            }

            return (null !== $delimiter) ? implode($delimiter, $values) : $values;
        }
    }

    /**
     * Set the header wrap
     *
     * @param  int $wrap
     * @return Header
     */
    public function setWrap($wrap)
    {
        $this->wrap = (int)$wrap;
        return $this;
    }

    /**
     * Get the header wrap
     *
     * @return int
     */
    public function getWrap()
    {
        return $this->wrap;
    }

    /**
     * Has header wrap
     *
     * @return boolean
     */
    public function hasWrap()
    {
        return (null !== $this->wrap);
    }

    /**
     * Set the header wrap indent
     *
     * @param  string $indent
     * @return Header
     */
    public function setIndent($indent)
    {
        $this->indent = $indent;
        return $this;
    }

    /**
     * Get the header wrap indent
     *
     * @return string
     */
    public function getIndent()
    {
        return $this->indent;
    }

    /**
     * Has header wrap indent
     *
     * @return boolean
     */
    public function hasIndent()
    {
        return (null !== $this->indent);
    }

    /**
     * Is the header for an attachment
     *
     * @return boolean
     */
    public function isAttachment()
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
    public function render()
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
    public function __toString()
    {
        return $this->render();
    }

}