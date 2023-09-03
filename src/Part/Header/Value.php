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
namespace Pop\Mime\Part\Header;

/**
 * MIME part header value class
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.2.0
 */
class Value
{

    /**
     * Header value scheme
     * @var string
     */
    protected $scheme = null;

    /**
     * Header value
     * @var string
     */
    protected $value = null;

    /**
     * Header value parameters
     * @var array
     */
    protected $parameters = [];

    /**
     * Header value delimiter
     * @var string
     */
    protected $delimiter = ';';

    /**
     * Constructor
     *
     * Instantiate the header value object
     *
     * @param string $value
     * @param string $scheme
     * @param array  $parameters
     */
    public function __construct($value = null, $scheme = null, array $parameters = [])
    {
        if (null !== $value) {
            $this->setValue($value);
        }
        if (null !== $scheme) {
            $this->setScheme($scheme);
        }
        if (!empty($parameters)) {
            $this->addParameters($parameters);
        }
    }

    /**
     * Parse header value
     *
     * @param  string $value
     * @return Value
     */
    public static function parse($value)
    {
        $valueObject = new Value();
        $parameters  = [];

        if ((strpos($value, ';') !== false) || (strpos($value, ',') !== false)) {
            $delimiter = (strpos($value, ';') !== false) ? ';' : ',';
            $valueObject->setDelimiter($delimiter);

            $matches = [];
            preg_match_all('/\w+=[\\a-zA-Z0-9_\s\.\"\/]/mi', $value, $matches, PREG_OFFSET_CAPTURE);
            if (isset($matches[0]) && isset($matches[0][0]) && isset($matches[0][0][1])) {
                $val = trim(str_replace($delimiter, '', substr($value, 0, $matches[0][0][1])));

                if ((stripos($val, 'Basic') !== false) || (stripos($val, 'Bearer') !== false) || (stripos($val, 'Digest') !== false)) {
                    if (strpos($val, ' ') !== false) {
                        $valueObject->setScheme(substr($val, 0, strpos($val, ' ')) . ' ');
                        $valueObject->setValue(substr($val, (strpos($val, ' ') + 1)));
                    } else {
                        $valueObject->setScheme($val . ' ');
                    }


                } else {
                    $valueObject->setValue($val);
                }

                $value  = trim(substr($value, $matches[0][0][1]));
                $params = array_map('trim', explode($delimiter, $value));
                foreach ($params as $param) {
                    if (strpos($param, '=') !== false) {
                        [$paramName, $paramValue] = self::parseParameter($param);
                        $parameters[$paramName] = $paramValue;
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
     * Set the header value scheme
     *
     * @param  string $scheme
     * @return Value
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * Get the header value scheme
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Has a header value scheme
     *
     * @return boolean
     */
    public function hasScheme()
    {
        return (null !== $this->delimiter);
    }

    /**
     * Set the header value
     *
     * @param  string $value
     * @return Value
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
     * Add the header value parameters
     *
     * @param  array $parameters
     * @return Value
     */
    public function addParameters(array $parameters)
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
    public function addParameter($name, $value)
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * Get the header value parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Get the header value parameters as string
     *
     * @throws Exception
     * @return string
     */
    public function getParametersAsString()
    {
        if (!$this->hasDelimiter()) {
            throw new Exception('Error: No delimiter has been set.');
        }

        $parameters = [];

        foreach ($this->parameters as $name => $value) {
            if ((strpos($value, ' ') !== false) && (strpos($value, '"') === false)) {
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
     * @return string
     */
    public function getParameter($name)
    {
        return (isset($this->parameters[$name])) ? $this->parameters[$name] : null;
    }

    /**
     * Has header value parameters
     *
     * @return boolean
     */
    public function hasParameters()
    {
        return (count($this->parameters) > 0);
    }

    /**
     * Has a header value parameter
     *
     * @param  string $name
     * @return boolean
     */
    public function hasParameter($name)
    {
        return (isset($this->parameters[$name]));
    }

    /**
     * Set the header value delimiter
     *
     * @param  string $delimiter
     * @return Value
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * Get the header value delimiter
     *
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * Has a header value delimiter
     *
     * @return boolean
     */
    public function hasDelimiter()
    {
        return (null !== $this->delimiter);
    }

    /**
     * Render the header value string
     *
     * @return string
     */
    public function render()
    {
        $value = $this->scheme . $this->value;

        if (count($this->parameters) > 0) {
            $parameters = $this->getParametersAsString();
            if (substr($value, -1) != ' ') {
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
    public function __toString()
    {
        return $this->render();
    }

}