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
     * @var string|array
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
        $value      = null;
        $parameters = [];

        // Handle multiple values
        if (substr_count($header, $name) > 1) {
            $values = array_map('trim', array_filter(explode($name . ':', $header)));
            foreach ($values as $i => $value) {
                if (strpos($value, ';') !== false) {
                    $params     = array_map('trim', explode(';', trim(substr($value,  (strpos($value, ';') + 1)))));
                    $values[$i] = trim(substr($value, 0, strpos($value, ';')));
                    foreach ($params as $param) {
                        if (strpos($param, '=') !== false) {
                            [$paramName, $paramValue] = self::parseParameter($param);
                            $parameters[$paramName] = $paramValue;
                        }
                    }
                }
            }
            return new self($name, $values, $parameters);
        } else {
            if (strpos($header, ';') !== false) {
                $value  = substr($header, (strpos($header, ':') + 1));
                $value  = trim(substr($value, 0, strpos($value, ';')));
                $params = array_map('trim', explode(';', trim(substr($header,  (strpos($header, ';') + 1)))));
                foreach ($params as $param) {
                    if (strpos($param, '=') !== false) {
                        [$paramName, $paramValue] = self::parseParameter($param);
                        $parameters[$paramName] = $paramValue;
                    }
                }
            } else {
                $value = trim(substr($header, (strpos($header, ':') + 1)));
            }

            return new self($name, $value, $parameters);
        }
    }

    /**
     * Render the header string
     *
     * @param  string $parameter
     * @return array
     */
    public static function parseParameter($parameter)
    {
        $paramName  = substr($parameter, 0, strpos($parameter, '='));
        $paramValue = substr($parameter, (strpos($parameter, '=')+ 1));
        if ((substr($paramValue, 0, 1) == '"') && (substr($paramValue, -1) == '"')) {
            $paramValue = substr($paramValue, 1);
            $paramValue = substr($paramValue, 0, -1);
        }
        return [$paramName, $paramValue];
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
     * @return string|array
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
     * Get the header parameters as string
     *
     * @return string
     */
    public function getParametersAsString()
    {
        $parameters = [];

        foreach ($this->parameters as $name => $value) {
            if (strpos($value, ' ') !== false) {
                $value = '"' . $value . '"';
            }
            $parameters[] = $name . '=' . $value;
        }

        return implode('; ', $parameters);
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
        return (($this->name == 'Content-Disposition') &&
            (($this->value == 'attachment') || ($this->value == 'inline')));
    }

    /**
     * Render the header string
     *
     * @return string
     */
    public function render()
    {
        $parameters = [];

        if (count($this->parameters) > 0) {
            $parameters = [];
            foreach ($this->parameters as $name => $value) {
                if (strpos($value, ' ') !== false) {
                    $value = '"' . $value . '"';
                }
                $parameters[] = $name . '=' . $value;
            }
        }

        if (is_array($this->value)) {
            $headers = [];
            foreach ($this->value as $value) {
                $hdr = $this->name . ': ' . $value;

                if (count($parameters) > 0) {
                    $hdr .= '; ' . implode('; ', $parameters);
                }

                if ((int)$this->wrap !== 0) {
                    $hdr = wordwrap($hdr, $this->wrap, "\r\n" . $this->indent);
                }
                $headers[] = $hdr;
            }
            $header = implode("\r\n", $headers);
        } else {
            $header = $this->name . ': ' . $this->value;

            if (count($parameters) > 0) {
                $header .= '; ' . implode('; ', $parameters);
            }

            if ((int)$this->wrap !== 0) {
                $header = wordwrap($header, $this->wrap, "\r\n" . $this->indent);
            }
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