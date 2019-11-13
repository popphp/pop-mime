<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.0.0
 */
class Header
{

    /**
     * Header name
     * @var string
     */
    protected $name = null;

    /**
     * Header value
     * @var string
     */
    protected $value = null;

    /**
     * Header parameters
     * @var array
     */
    protected $parameters = [];

    /**
     * Header wrap
     * @var int
     */
    protected $wrap = 76;

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
     * @param string $value
     * @param array  $parameters
     */
    public function __construct($name = null, $value = null, array $parameters = [])
    {
        if (null !== $name) {
            $this->setName($name);
        }
        if (null !== $value) {
            $this->setValue($value);
        }
        if (!empty($parameters)) {
            $this->addParameters($parameters);
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
        $name       = trim(substr($header, 0, strpos($header, ':')));
        $parameters = [];

        if (strpos($header, ';') !== false) {
            $value  = substr($header, (strpos($header, ':') + 1));
            $value  = trim(substr($value, 0, strpos($value, ';')));
            $params = array_map('trim', explode(';', trim(substr($header,  (strpos($header, ';') + 1)))));
            foreach ($params as $param) {
                if (strpos($param, '=') !== false) {
                    [$paramName, $paramValue] = explode('=', $param);
                    if ((substr($paramValue, 0, 1) == '"') && (substr($paramValue, -1) == '"')) {
                        $paramValue = substr($paramValue, 1);
                        $paramValue = substr($paramValue, 0, -1);
                    }
                    $parameters[$paramName] = $paramValue;
                }
            }
        } else {
            $value = trim(substr($header, (strpos($header, ':') + 1)));
        }

        return new self($name, $value, $parameters);
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
     * Set the header value
     *
     * @param  string $value
     * @return Header
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get the header value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Add the header parameters
     *
     * @param  array $parameters
     * @return Header
     */
    public function addParameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Set a header parameter
     *
     * @param string $name
     * @param string $value
     * @return Header
     */
    public function addParameter($name, $value)
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * Get the header parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Get a header parameter
     *
     * @param  string $name
     * @return string
     */
    public function getParameter($name)
    {
        return (isset($this->parameters[$name])) ? $this->parameters[$name] : null;
    }

    /**
     * Has header parameters
     *
     * @return boolean
     */
    public function hasParameters()
    {
        return (count($this->parameters) > 0);
    }

    /**
     * Has a header parameter
     *
     * @param  string $name
     * @return boolean
     */
    public function hasParameter($name)
    {
        return (isset($this->parameters[$name]));
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
     * Render the header string
     *
     * @return string
     */
    public function render()
    {
        $header = $this->name . ': ' . $this->value;

        if (count($this->parameters) > 0) {
            $parameters = [];
            foreach ($this->parameters as $name => $value) {
                if (strpos($value, ' ') !== false) {
                    $value = '"' . $value . '"';
                }
                $parameters[] = $name . '=' . $value;
            }

            $header .= '; ' . implode('; ', $parameters);
        }

        if (null !== $this->wrap) {
            $header = wordwrap($header, $this->wrap, "\r\n" . $this->indent);
        }

        return $header;
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